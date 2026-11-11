<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Ensure only administrators and dentists can access audit logs.
     */
    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'dentist'])) {
            abort(403, 'Access denied. Audit logs are restricted to clinic administrators and attending dentists.');
        }
    }

    /**
     * Display a listing of audit logs with filtering and metrics.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = AuditLog::with('user:id,name,email,role')
            ->search($request->search)
            ->module($request->module)
            ->actionType($request->action)
            ->role($request->role);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $todayStr = now()->toDateString();

        $metrics = [
            'total_logs' => AuditLog::count(),
            'today_logs' => AuditLog::whereDate('created_at', $todayStr)->count(),
            'clinical_operations' => AuditLog::whereIn('module', ['appointments', 'patients', 'treatments', 'medical_history'])->count(),
            'financial_operations' => AuditLog::whereIn('module', ['billing', 'payments'])->count(),
        ];

        // Available modules & actions present in DB
        $availableModules = AuditLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->values();

        $availableActions = AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        return Inertia::render('AuditLog/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'module', 'action', 'role', 'date_from', 'date_to']),
            'metrics' => $metrics,
            'availableModules' => $availableModules,
            'availableActions' => $availableActions,
        ]);
    }

    /**
     * Export filtered audit logs as a downloadable CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();

        $query = AuditLog::with('user:id,name,email,role')
            ->search($request->search)
            ->module($request->module)
            ->actionType($request->action)
            ->role($request->role);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest('created_at')->latest('id')->get();

        $filename = 'vitality_audit_log_' . now()->format('Ymd_His') . '.csv';

        // Audit log the export action itself
        AuditLogger::log(
            module: 'audit_log',
            action: 'exported',
            description: "Admin exported " . count($logs) . " audit log records to CSV.",
        );

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Log ID',
                'Date & Time (PHT)',
                'Actor Name',
                'Actor Role',
                'Module',
                'Action',
                'Description',
                'Auditable Model',
                'Auditable ID',
                'IP Address',
                'User Agent',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    Carbon::parse($log->created_at)->timezone('Asia/Manila')->format('Y-m-d h:i:s A'),
                    $log->user_name ?? ($log->user?->name ?? 'System'),
                    strtoupper($log->user_role ?? 'SYSTEM'),
                    strtoupper($log->module),
                    strtoupper($log->action),
                    $log->description,
                    $log->auditable_type ? class_basename($log->auditable_type) : 'N/A',
                    $log->auditable_id ?? 'N/A',
                    $log->ip_address ?? '127.0.0.1',
                    $log->user_agent ?? 'N/A',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Purge audit logs older than a given number of days.
     */
    public function clearOld(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'days' => 'required|integer|in:30,60,90,180,365',
        ]);

        $days = (int) $validated['days'];
        $cutoffDate = now()->subDays($days);

        $count = AuditLog::where('created_at', '<', $cutoffDate)->count();

        AuditLog::where('created_at', '<', $cutoffDate)->delete();

        AuditLogger::log(
            module: 'audit_log',
            action: 'purged',
            description: "Admin purged {$count} audit log entries older than {$days} days (before {$cutoffDate->toDateString()}).",
        );

        return back()->with('success', "Successfully purged {$count} audit log records older than {$days} days.");
    }
}

