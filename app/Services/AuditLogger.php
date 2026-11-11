<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Keys to remove from diffs to prevent leaking secrets.
     */
    protected static array $hiddenAttributes = [
        'password',
        'remember_token',
        'token',
        'secret',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Record an audit log entry.
     */
    public static function log(
        string $module,
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $actor = null
    ): AuditLog {
        $user = $actor ?? Auth::user();

        $ip = null;
        $userAgent = null;
        if (app()->bound('request')) {
            $request = request();
            $ip = $request->ip();
            $userAgent = $request->userAgent();
        }

        $sanitizedOld = $oldValues !== null ? self::sanitize($oldValues) : null;
        $sanitizedNew = $newValues !== null ? self::sanitize($newValues) : null;

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System / Guest',
            'user_role' => $user?->role ?? 'system',
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model ? $model->getKey() : null,
            'old_values' => $sanitizedOld,
            'new_values' => $sanitizedNew,
            'ip_address' => $ip ?? '127.0.0.1',
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Sanitize array values to strip password / token fields.
     */
    protected static function sanitize(array $data): array
    {
        foreach (self::$hiddenAttributes as $key) {
            unset($data[$key]);
        }
        return $data;
    }
}

