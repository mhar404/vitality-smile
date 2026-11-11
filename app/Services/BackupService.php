<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackupService
{
    /**
     * Directory path where backups are stored.
     */
    public static function getBackupDirectory(): string
    {
        $dir = storage_path('app/backups');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get active SQLite database file path.
     */
    public static function getDatabasePath(): string
    {
        return database_path('database.sqlite');
    }

    /**
     * Format byte sizes into readable units (KB, MB, GB).
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Retrieve health metrics, record counts, and database stats.
     */
    public static function getDatabaseStats(): array
    {
        $dbPath = self::getDatabasePath();
        $dbSize = File::exists($dbPath) ? File::size($dbPath) : 0;

        $tableCounts = [
            'users' => User::count(),
            'patients' => Patient::count(),
            'appointments' => Appointment::count(),
            'treatments' => Treatment::count(),
            'invoices' => Invoice::count(),
            'payments' => Payment::count(),
            'services' => Service::count(),
            'medical_histories' => MedicalHistory::count(),
            'audit_logs' => AuditLog::count(),
        ];

        $backups = self::listBackups();
        $totalBackupSize = array_sum(array_column($backups, 'size'));
        $lastBackup = !empty($backups) ? $backups[0] : null;

        return [
            'database_size' => $dbSize,
            'database_size_formatted' => self::formatBytes($dbSize),
            'database_file' => basename($dbPath),
            'table_counts' => $tableCounts,
            'total_records' => array_sum($tableCounts),
            'total_backups' => count($backups),
            'total_backup_size' => $totalBackupSize,
            'total_backup_size_formatted' => self::formatBytes($totalBackupSize),
            'last_backup_date' => $lastBackup ? $lastBackup['created_at'] : null,
        ];
    }

    /**
     * List all available backup archives with metadata.
     */
    public static function listBackups(): array
    {
        $dir = self::getBackupDirectory();
        $files = File::files($dir);

        $backups = [];
        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['zip', 'sqlite'])) {
                continue;
            }

            $size = $file->getSize();
            $modifiedTime = $file->getMTime();
            $filename = $file->getFilename();

            $manifest = null;
            $type = str_contains($filename, 'full') ? 'Full System' : 'Database Snapshot';
            $isRollback = str_contains($filename, 'rollback') || str_contains($filename, 'pre_restore');

            if ($ext === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($file->getRealPath()) === true) {
                    $manifestIndex = $zip->locateName('manifest.json');
                    if ($manifestIndex !== false) {
                        $manifestContent = $zip->getFromIndex($manifestIndex);
                        $manifest = json_decode($manifestContent, true);
                    }
                    $zip->close();
                }
            }

            $backups[] = [
                'filename' => $filename,
                'file_path' => $file->getRealPath(),
                'size' => $size,
                'size_formatted' => self::formatBytes($size),
                'created_at' => Carbon::createFromTimestamp($modifiedTime)->toISOString(),
                'type' => $type,
                'is_rollback' => $isRollback,
                'manifest' => $manifest,
                'sha256' => hash_file('sha256', $file->getRealPath()),
            ];
        }

        // Sort descending by file creation/modification time
        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Create a backup archive (Database Snapshot or Full System).
     */
    public static function createBackup(string $type = 'database', ?string $note = null, ?User $actor = null): array
    {
        $user = $actor ?? Auth::user();
        $dir = self::getBackupDirectory();
        $dbPath = self::getDatabasePath();

        if (!File::exists($dbPath)) {
            throw new Exception("Active database file not found at: {$dbPath}");
        }

        $timestamp = now()->format('Y-m-d_His');
        $prefix = $type === 'full' ? 'vitality_full_backup' : 'vitality_db_backup';
        $filename = "{$prefix}_{$timestamp}.zip";
        $zipPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $dbChecksum = hash_file('sha256', $dbPath);
        $stats = self::getDatabaseStats();

        $manifestData = [
            'application' => 'Vitality Smile Dental Clinic ERP',
            'version' => '1.0.0',
            'backup_type' => $type === 'full' ? 'full_system' : 'database_only',
            'created_at' => now()->toIso8601String(),
            'created_by' => $user?->name ?? 'System Administrator',
            'created_by_role' => $user?->role ?? 'admin',
            'note' => $note,
            'database_file' => 'database.sqlite',
            'database_size' => File::size($dbPath),
            'database_sha256' => $dbChecksum,
            'table_counts' => $stats['table_counts'],
            'total_records' => $stats['total_records'],
        ];

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot create backup zip archive at {$zipPath}");
        }

        // Add database file
        $zip->addFile($dbPath, 'database.sqlite');

        // Add metadata manifest and checksum files
        $zip->addFromString('manifest.json', json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('checksum.sha256', "{$dbChecksum}  database.sqlite\n");

        // If full system backup requested, archive public upload storage assets (avatars, attachments)
        if ($type === 'full') {
            $publicUploads = storage_path('app/public');
            if (File::isDirectory($publicUploads)) {
                $uploadFiles = File::allFiles($publicUploads);
                foreach ($uploadFiles as $uFile) {
                    $relative = 'storage/' . $uFile->getRelativePathname();
                    $zip->addFile($uFile->getRealPath(), $relative);
                }
            }
        }

        $zip->close();

        // Audit log event
        AuditLogger::log(
            module: 'backup',
            action: 'created',
            description: "Admin created {$type} backup: {$filename} (" . self::formatBytes(File::size($zipPath)) . ").",
            oldValues: null,
            newValues: [
                'filename' => $filename,
                'type' => $type,
                'size' => File::size($zipPath),
                'total_records' => $stats['total_records'],
            ],
            actor: $user
        );

        return [
            'filename' => $filename,
            'file_path' => $zipPath,
            'size' => File::size($zipPath),
            'size_formatted' => self::formatBytes(File::size($zipPath)),
            'manifest' => $manifestData,
        ];
    }

    /**
     * Restore database from an existing backup in storage.
     * Automatically creates an emergency pre-restore rollback snapshot before applying.
     */
    public static function restoreBackup(string $filename, ?User $actor = null): array
    {
        $user = $actor ?? Auth::user();
        $dir = self::getBackupDirectory();
        $backupPath = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($backupPath)) {
            throw new Exception("Backup archive file not found: {$filename}");
        }

        $dbPath = self::getDatabasePath();

        // Step 1: Emergency Rollback Snapshot creation before touching active DB
        $rollbackTimestamp = now()->format('Y-m-d_His');
        $rollbackFilename = "rollback_before_restore_{$rollbackTimestamp}.zip";
        $rollbackPath = $dir . DIRECTORY_SEPARATOR . $rollbackFilename;

        $rollbackZip = new ZipArchive();
        if ($rollbackZip->open($rollbackPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $rollbackZip->addFile($dbPath, 'database.sqlite');
            $rollbackZip->addFromString('manifest.json', json_encode([
                'type' => 'emergency_pre_restore_rollback',
                'created_at' => now()->toIso8601String(),
                'target_restore_file' => $filename,
                'actor' => $user?->name ?? 'System Admin',
            ], JSON_PRETTY_PRINT));
            $rollbackZip->close();
        }

        // Step 2: Extract & Verify SQLite file from backup
        $tempDir = storage_path('app/temp_restore_' . uniqid());
        File::makeDirectory($tempDir, 0755, true);

        try {
            $extractedDbPath = null;
            $ext = strtolower(pathinfo($backupPath, PATHINFO_EXTENSION));

            if ($ext === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($backupPath) !== true) {
                    throw new Exception("Unable to open backup archive file.");
                }

                $dbIndex = $zip->locateName('database.sqlite');
                if ($dbIndex === false) {
                    // Check if any .sqlite exists in zip
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        if (str_ends_with(strtolower($stat['name']), '.sqlite')) {
                            $dbIndex = $i;
                            break;
                        }
                    }
                }

                if ($dbIndex === false) {
                    $zip->close();
                    throw new Exception("Corrupted or invalid backup: No SQLite database found inside archive.");
                }

                $zip->extractTo($tempDir);
                $stat = $zip->statIndex($dbIndex);
                $extractedDbPath = $tempDir . DIRECTORY_SEPARATOR . $stat['name'];
                $zip->close();
            } elseif ($ext === 'sqlite') {
                $extractedDbPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sqlite';
                File::copy($backupPath, $extractedDbPath);
            }

            if (!$extractedDbPath || !File::exists($extractedDbPath)) {
                throw new Exception("Failed to extract database from backup.");
            }

            // Verify SQLite file header
            $header = file_get_contents($extractedDbPath, false, null, 0, 16);
            if (!str_starts_with($header, 'SQLite format 3')) {
                throw new Exception("Corrupted file: The extracted file is not a valid SQLite database.");
            }

            // Step 3: Disconnect database connection to release file lock
            DB::disconnect();

            // Step 4: Overwrite active database file
            File::copy($extractedDbPath, $dbPath);

            // Step 5: Reconnect & test DB
            DB::reconnect();
            $patientCount = Patient::count();
            $appointmentCount = Appointment::count();

            // Audit log the restoration
            AuditLogger::log(
                module: 'backup',
                action: 'restored',
                description: "Admin restored system data from backup: {$filename}. Emergency rollback created: {$rollbackFilename}.",
                oldValues: ['rollback_file' => $rollbackFilename],
                newValues: [
                    'restored_from' => $filename,
                    'patient_count' => $patientCount,
                    'appointment_count' => $appointmentCount,
                ],
                actor: $user
            );

            return [
                'success' => true,
                'restored_from' => $filename,
                'rollback_file' => $rollbackFilename,
                'patients' => $patientCount,
                'appointments' => $appointmentCount,
            ];
        } finally {
            // Clean up temporary extraction folder
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Restore from an uploaded file (.zip or .sqlite).
     */
    public static function restoreFromUpload(UploadedFile $file, ?User $actor = null): array
    {
        $dir = self::getBackupDirectory();
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['zip', 'sqlite'])) {
            throw new Exception("Invalid file format. Please upload a .zip or .sqlite backup archive.");
        }

        $safeName = 'uploaded_backup_' . now()->format('Y-m-d_His') . '.' . $ext;
        $file->move($dir, $safeName);

        return self::restoreBackup($safeName, $actor);
    }

    /**
     * Permanently delete a backup file.
     */
    public static function deleteBackup(string $filename, ?User $actor = null): bool
    {
        $user = $actor ?? Auth::user();
        $dir = self::getBackupDirectory();
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($path)) {
            return false;
        }

        $size = File::size($path);
        File::delete($path);

        AuditLogger::log(
            module: 'backup',
            action: 'deleted',
            description: "Admin deleted backup archive: {$filename} (" . self::formatBytes($size) . ").",
            oldValues: ['filename' => $filename, 'size' => $size],
            actor: $user
        );

        return true;
    }
}

