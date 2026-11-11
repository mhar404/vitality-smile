<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'dentist_id',
        'appointment_id',
        'procedure_name',
        'tooth_number',
        'diagnosis',
        'treatment_notes',
        'prescription',
        'cost',
        'status',
        'treatment_date',
        'medical_clearance_presented',
        'medical_clearance_doctor',
        'medical_clearance_date',
        'medical_clearance_notes',
        'medical_clearance_file',
    ];

    protected $casts = [
        'treatment_date' => 'date:Y-m-d',
        'medical_clearance_date' => 'date:Y-m-d',
        'medical_clearance_presented' => 'boolean',
        'cost' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
