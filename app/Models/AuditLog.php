<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'module',
        'action',
        'description',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSearch($query, ?string $search)
    {
        if (blank($search)) {
            return $query;
        }

        $term = '%' . trim($search) . '%';
        return $query->where(function ($q) use ($term) {
            $q->where('description', 'like', $term)
              ->orWhere('user_name', 'like', $term)
              ->orWhere('ip_address', 'like', $term)
              ->orWhere('action', 'like', $term)
              ->orWhere('module', 'like', $term);
        });
    }

    public function scopeModule($query, ?string $module)
    {
        if (blank($module) || $module === 'all') {
            return $query;
        }

        return $query->where('module', $module);
    }

    public function scopeActionType($query, ?string $action)
    {
        if (blank($action) || $action === 'all') {
            return $query;
        }

        return $query->where('action', $action);
    }

    public function scopeRole($query, ?string $role)
    {
        if (blank($role) || $role === 'all') {
            return $query;
        }

        return $query->where('user_role', $role);
    }
}

