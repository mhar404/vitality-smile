<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'category',
        'base_price',
        'max_price',
        'price_display',
        'duration',
        'duration_minutes',
        'icon',
        'description',
        'highlights',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'highlights' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected $appends = [
        'formatted_price',
        'duration_label',
    ];

    /**
     * Compute a human-readable duration label.
     */
    public function getDurationLabelAttribute(): string
    {
        $minutes = $this->duration_minutes ?: 60;
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }
        if ($minutes % 60 === 0) {
            $hours = $minutes / 60;
            return $hours === 1 ? '1 hour (60 mins)' : "{$hours} hours ({$minutes} mins)";
        }
        $hours = floor($minutes / 60);
        $rem = $minutes % 60;
        return "{$hours} hr {$rem} mins";
    }

    /**
     * Compute a human-readable price label if not explicitly customized.
     */
    public function getFormattedPriceAttribute(): string
    {
        if (!empty($this->price_display)) {
            return $this->price_display;
        }

        if ($this->base_price <= 0 && (!$this->max_price || $this->max_price <= 0)) {
            return 'Consultation & Custom Quote';
        }

        $baseFormatted = '₱' . number_format((float) $this->base_price, 0);

        if ($this->max_price && $this->max_price > $this->base_price) {
            $maxFormatted = '₱' . number_format((float) $this->max_price, 0);
            return "{$baseFormatted} - {$maxFormatted}";
        }

        return $baseFormatted;
    }

    /**
     * Scope for active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered services.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }
}
