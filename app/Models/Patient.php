<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'patient_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'date_of_birth',
        'gender',
        'guardian',
        'contact_number',
        'email',
        'address',
        'status',
    ];

    protected $appends = ['age', 'full_name', 'has_medical_history'];

    public function getHasMedicalHistoryAttribute(): bool
    {
        return (bool)($this->relationLoaded('medicalHistory') && $this->medicalHistory && $this->medicalHistory->hasConditions());
    }

    public function getGuardianNameAttribute(): ?string
    {
        return $this->attributes['guardian'] ?? null;
    }

    public function setGuardianNameAttribute(?string $value): void
    {
        $this->attributes['guardian'] = $value;
    }

    public function getAgeAttribute()
    {
        if (empty($this->attributes['date_of_birth'])) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($this->attributes['date_of_birth'])->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getFullNameAttribute()
    {
        $first = $this->first_name ?? '';
        $middle = !empty($this->middle_name) ? "{$this->middle_name} " : '';
        $last = $this->last_name ?? '';
        $suffix = !empty($this->suffix) ? " {$this->suffix}" : '';

        return trim("{$first} {$middle}{$last}{$suffix}");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class)->orderBy('appointment_date', 'desc')->orderBy('appointment_time', 'desc');
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class)->orderBy('treatment_date', 'desc')->orderBy('created_at', 'desc');
    }

    public function medicalHistory()
    {
        return $this->hasOne(MedicalHistory::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class)->orderBy('invoice_date', 'desc')->orderBy('id', 'desc');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
    }
}
