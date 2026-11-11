<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'blood_type',
        'has_allergies',
        'allergies',
        'hypertension',
        'diabetes',
        'heart_disease',
        'bleeding_disorders',
        'asthma',
        'kidney_disease',
        'liver_disease',
        'hepatitis',
        'epilepsy',
        'is_pregnant',
        'other_conditions',
        'current_medications',
        'past_surgeries',
        'physician_name',
        'physician_contact',
        'emergency_contact_name',
        'emergency_contact_number',
        'emergency_contact_relation',
        'notes',
        'medical_clearance_presented',
        'medical_clearance_doctor',
        'medical_clearance_date',
        'medical_clearance_notes',
        'medical_clearance_file',
    ];

    protected $casts = [
        'has_allergies' => 'boolean',
        'hypertension' => 'boolean',
        'diabetes' => 'boolean',
        'heart_disease' => 'boolean',
        'bleeding_disorders' => 'boolean',
        'asthma' => 'boolean',
        'kidney_disease' => 'boolean',
        'liver_disease' => 'boolean',
        'hepatitis' => 'boolean',
        'epilepsy' => 'boolean',
        'is_pregnant' => 'boolean',
        'medical_clearance_presented' => 'boolean',
        'medical_clearance_date' => 'date:Y-m-d',
        'other_conditions' => 'array',
    ];

    protected $appends = ['critical_alerts', 'has_medical_conditions'];

    /**
     * Determine if patient has any recorded medical history or chronic conditions.
     */
    public function hasConditions(): bool
    {
        return (bool)(
            $this->hypertension
            || $this->diabetes
            || $this->heart_disease
            || $this->bleeding_disorders
            || $this->asthma
            || $this->kidney_disease
            || $this->liver_disease
            || $this->hepatitis
            || $this->epilepsy
            || $this->is_pregnant
            || $this->has_allergies
            || !empty($this->allergies)
            || !empty($this->current_medications)
            || !empty($this->other_conditions)
            || !empty($this->past_surgeries)
        );
    }

    public function getHasMedicalConditionsAttribute(): bool
    {
        return $this->hasConditions();
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get list of high-priority clinical medical alerts for this patient.
     */
    public function getCriticalAlertsAttribute(): array
    {
        $alerts = [];

        if ($this->has_allergies || !empty($this->allergies)) {
            $alerts[] = [
                'type' => 'danger',
                'label' => 'Allergies: ' . ($this->allergies ?: 'Present'),
            ];
        }

        if ($this->bleeding_disorders) {
            $alerts[] = [
                'type' => 'danger',
                'label' => 'Bleeding Disorder',
            ];
        }

        if ($this->heart_disease) {
            $alerts[] = [
                'type' => 'danger',
                'label' => 'Cardiac / Heart Condition',
            ];
        }

        if ($this->hypertension) {
            $alerts[] = [
                'type' => 'warning',
                'label' => 'Hypertension',
            ];
        }

        if ($this->diabetes) {
            $alerts[] = [
                'type' => 'warning',
                'label' => 'Diabetes',
            ];
        }

        if ($this->asthma) {
            $alerts[] = [
                'type' => 'warning',
                'label' => 'Asthma',
            ];
        }

        if ($this->is_pregnant) {
            $alerts[] = [
                'type' => 'info',
                'label' => 'Pregnant Patient',
            ];
        }

        if ($this->epilepsy) {
            $alerts[] = [
                'type' => 'warning',
                'label' => 'Epilepsy / Seizures',
            ];
        }

        if ($this->hepatitis) {
            $alerts[] = [
                'type' => 'danger',
                'label' => 'Hepatitis',
            ];
        }

        if ($this->kidney_disease) {
            $alerts[] = [
                'type' => 'warning',
                'label' => 'Kidney Disease',
            ];
        }

        if ($this->liver_disease) {
            $alerts[] = [
                'type' => 'warning',
                'label' => 'Liver Disease',
            ];
        }

        return $alerts;
    }
}
