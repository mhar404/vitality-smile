<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\BackupService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /**
     * Enforce administrator and dentist access control.
     */
    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'dentist'])) {
            abort(403, 'Access denied. System backup and disaster recovery is restricted to clinic administrators and attending dentists.');
        }
    }

    /**
     * Display a listing of system backups and database health metrics.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $stats = BackupService::getDatabaseStats();
        $allBackups = collect(BackupService::listBackups());

        $perPage = 8;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $backups = new LengthAwarePaginator(
            $allBackups->forPage($page, $perPage)->values(),
            $allBackups->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Backup/Index', [
            'stats' => $stats,
            'backups' => $backups,
        ]);
    }

    /**
     * Create a new backup archive (Database Snapshot or Full System).
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'type' => 'required|in:database,full',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $result = BackupService::createBackup(
                type: $validated['type'],
                note: $validated['note'] ?? null,
                actor: Auth::user()
            );

            return back()->with('success', "Backup archive '{$result['filename']}' created successfully ({$result['size_formatted']}).");
        } catch (Exception $e) {
            return back()->with('error', "Backup creation failed: " . $e->getMessage());
        }
    }

    /**
     * Download a backup file securely to local device.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $this->authorizeAdmin();

        // Prevent path traversal
        $safeName = basename($filename);
        $path = BackupService::getBackupDirectory() . DIRECTORY_SEPARATOR . $safeName;

        if (!File::exists($path)) {
            abort(404, 'Backup file not found.');
        }

        AuditLogger::log(
            module: 'backup',
            action: 'downloaded',
            description: "Admin downloaded backup archive '{$safeName}'.",
            actor: Auth::user()
        );

        return response()->download($path, $safeName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Restore system database from an existing server backup.
     */
    public function restore(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'filename' => 'required|string',
            'confirmation' => 'required|string|in:RESTORE',
        ], [
            'confirmation.in' => 'Please type RESTORE exactly to confirm the restoration procedure.',
        ]);

        $safeName = basename($validated['filename']);

        try {
            $result = BackupService::restoreBackup($safeName, Auth::user());

            return back()->with(
                'success',
                "System data successfully restored from '{$safeName}'! An emergency rollback snapshot was automatically preserved as '{$result['rollback_file']}'."
            );
        } catch (Exception $e) {
            return back()->with('error', "Restoration failed: " . $e->getMessage());
        }
    }

    /**
     * Upload an external backup file and restore system data.
     */
    public function restoreUpload(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'backup_file' => 'required|file|max:102400', // up to 100MB
            'confirmation' => 'required|string|in:RESTORE',
        ], [
            'confirmation.in' => 'Please type RESTORE exactly to confirm the restoration procedure.',
        ]);

        try {
            $result = BackupService::restoreFromUpload($request->file('backup_file'), Auth::user());

            return back()->with(
                'success',
                "System data successfully restored from uploaded backup! An emergency rollback snapshot was automatically preserved as '{$result['rollback_file']}'."
            );
        } catch (Exception $e) {
            return back()->with('error', "Uploaded restore failed: " . $e->getMessage());
        }
    }

    /**
     * Delete an existing backup file.
     */
    public function destroy(string $filename)
    {
        $this->authorizeAdmin();

        $safeName = basename($filename);

        $deleted = BackupService::deleteBackup($safeName, Auth::user());

        if (!$deleted) {
            return back()->with('error', "Backup archive '{$safeName}' not found or could not be removed.");
        }

        return back()->with('success', "Backup archive '{$safeName}' was successfully deleted.");
    }
}

