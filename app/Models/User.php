<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'role',
        'is_active',
        'password',
        'google_id',
        'avatar',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function scopeSearch($query, ?string $search)
    {
        if (blank($search)) {
            return $query;
        }

        $term = '%' . trim($search) . '%';
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
              ->orWhere('email', 'like', $term)
              ->orWhere('phone_number', 'like', $term)
              ->orWhere('role', 'like', $term);
        });
    }

    public function scopeRoleFilter($query, ?string $role)
    {
        if (blank($role) || $role === 'all') {
            return $query;
        }

        return $query->where('role', $role);
    }

    public function scopeStatusFilter($query, ?string $status)
    {
        if (blank($status) || $status === 'all') {
            return $query;
        }

        if ($status === 'active') {
            return $query->where('is_active', true);
        }

        if ($status === 'inactive' || $status === 'suspended') {
            return $query->where('is_active', false);
        }

        return $query;
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function dentistAppointments()
    {
        return $this->hasMany(Appointment::class, 'dentist_id');
    }

    public function dentistTreatments()
    {
        return $this->hasMany(Treatment::class, 'dentist_id');
    }

    /**
     * Ensure the user has an associated Patient record when they are considered a patient.
     */
    public function ensurePatientProfile(): ?Patient
    {
        if ($this->role !== 'patient') {
            return null;
        }

        // 1. Check if patient record already linked to this user_id
        $patient = Patient::where('user_id', $this->id)->first();
        if ($patient) {
            return $patient;
        }

        // 2. Check if an existing patient record exists with the same email
        if (!empty($this->email)) {
            $existing = Patient::where('email', $this->email)->first();
            if ($existing) {
                $existing->update(['user_id' => $this->id]);
                return $existing;
            }
        }

        // 3. Create a new patient record
        $nameParts = preg_split('/\s+/', trim($this->name ?: 'Patient User'), 2);
        $firstName = $nameParts[0] ?: 'Patient';
        $lastName = $nameParts[1] ?? 'User';

        $count = Patient::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
        $patientId = 'PAT-' . now()->format('Ym') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        while (Patient::where('patient_id', $patientId)->exists()) {
            $count++;
            $patientId = 'PAT-' . now()->format('Ym') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return Patient::create([
            'user_id' => $this->id,
            'patient_id' => $patientId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $this->email,
            'status' => 'Active',
            'gender' => 'Other',
            'contact_number' => null,
            'date_of_birth' => null,
        ]);
    }
}
