<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'patient_id',
        'dentist_id',
        'service',
        'duration_minutes',
        'appointment_date',
        'appointment_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
    ];

    protected $appends = [
        'calculated_end_time',
        'formatted_time_window',
    ];

    protected static function booted(): void
    {
        static::saving(function ($appointment) {
            if (!empty($appointment->appointment_time)) {
                $duration = $appointment->duration_minutes ?: 60;
                $appointment->duration_minutes = $duration;
                $startTime = Carbon::parse($appointment->appointment_time);
                $appointment->end_time = $startTime->copy()->addMinutes($duration)->format('H:i:s');
            }
        });
    }

    public function getCalculatedEndTimeAttribute(): ?string
    {
        if (!empty($this->end_time)) {
            return $this->end_time;
        }
        if (!empty($this->appointment_time)) {
            return Carbon::parse($this->appointment_time)
                ->addMinutes($this->duration_minutes ?: 60)
                ->format('H:i:s');
        }
        return null;
    }

    public function getFormattedTimeWindowAttribute(): string
    {
        if (empty($this->appointment_time)) {
            return '';
        }
        $start = Carbon::parse($this->appointment_time)->format('g:i A');
        $end = Carbon::parse($this->calculated_end_time)->format('g:i A');
        $mins = $this->duration_minutes ?: 60;
        return "{$start} – {$end} ({$mins} mins)";
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function treatment()
    {
        return $this->hasOne(Treatment::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
