<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveMedicalHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'blood_type' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-,Unknown',
            'has_allergies' => 'boolean',
            'allergies' => 'nullable|string|max:1000',
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
            'other_conditions' => 'nullable|array',
            'current_medications' => 'nullable|string|max:1000',
            'past_surgeries' => 'nullable|string|max:1000',
            'physician_name' => 'nullable|string|max:255',
            'physician_contact' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:255',
            'emergency_contact_relation' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
