<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTreatmentRequest extends FormRequest
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
            'patient_id' => 'required|exists:patients,id',
            'dentist_id' => 'nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'procedure_name' => 'required|string|max:255',
            'tooth_number' => 'nullable|string|max:50',
            'diagnosis' => 'nullable|string',
            'treatment_notes' => 'nullable|string',
            'prescription' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|string|in:completed,in_progress,referred',
            'treatment_date' => 'required|date',
            'medical_clearance_presented' => 'nullable|boolean',
            'medical_clearance_doctor' => 'nullable|string|max:255',
            'medical_clearance_date' => 'nullable|date',
            'medical_clearance_notes' => 'nullable|string',
            'medical_clearance_file' => 'nullable|string',
        ];
    }

    /**
     * Configure the validator instance.
     * Enforce:
     * 1. Procedure fee strictly aligns with the dental procedure catalog price (no excess or less).
     * 2. Mandatory medical clearance presentation if patient has medical history.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Safety Gate: Enforce medical clearance if patient has recorded medical conditions
            if ($this->patient_id) {
                $patient = \App\Models\Patient::with('medicalHistory')->find($this->patient_id);
                if ($patient && $patient->medicalHistory && $patient->medicalHistory->hasConditions()) {
                    if (!$this->boolean('medical_clearance_presented')) {
                        $validator->errors()->add(
                            'medical_clearance_presented',
                            'This patient has recorded medical conditions. A physician medical clearance must be presented before the dentist can administer treatment.'
                        );
                    }
                    if (empty($this->medical_clearance_doctor)) {
                        $validator->errors()->add(
                            'medical_clearance_doctor',
                            'Please specify the clearing physician / doctor who issued the medical clearance.'
                        );
                    }
                }
            }

            $procedure = $this->procedure_name;
            $cost = (float) $this->cost;

            if (!$procedure) {
                return;
            }

            // 1. Look up matching service in database
            $service = \App\Models\Service::where('name', $procedure)
                ->orWhere('title', $procedure)
                ->first();

            if (!$service) {
                $service = \App\Models\Service::all()->first(function ($s) use ($procedure) {
                    $cleanProc = strtolower($procedure);
                    return str_contains($cleanProc, strtolower($s->name))
                        || str_contains($cleanProc, strtolower($s->title))
                        || str_contains(strtolower($s->name), $cleanProc)
                        || str_contains(strtolower($s->title), $cleanProc);
                });
            }

            if ($service && (float) $service->base_price > 0) {
                $min = (float) $service->base_price;
                $max = $service->max_price ? (float) $service->max_price : $min;

                if ($cost < $min) {
                    $validator->errors()->add(
                        'cost',
                        "The procedure fee cannot be less than ₱" . number_format($min, 2) . " for {$procedure}."
                    );
                } elseif ($cost > $max) {
                    $validator->errors()->add(
                        'cost',
                        "The procedure fee cannot exceed ₱" . number_format($max, 2) . " for {$procedure}."
                    );
                }
                return;
            }

            // 2. Parse price from procedure string if contains range or fixed price
            if (preg_match('/₱\s*([0-9,]+)\s*[-–]\s*₱?\s*([0-9,]+)/u', $procedure, $matches)) {
                $min = (float) str_replace(',', '', $matches[1]);
                $max = (float) str_replace(',', '', $matches[2]);
                if ($cost < $min) {
                    $validator->errors()->add('cost', "The procedure fee cannot be less than ₱" . number_format($min, 2) . " for {$procedure}.");
                } elseif ($cost > $max) {
                    $validator->errors()->add('cost', "The procedure fee cannot exceed ₱" . number_format($max, 2) . " for {$procedure}.");
                }
            } elseif (preg_match('/₱\s*([0-9,]+)/u', $procedure, $matches)) {
                $expected = (float) str_replace(',', '', $matches[1]);
                if ($cost != $expected) {
                    $validator->errors()->add('cost', "The procedure fee must align with the dental procedure price (₱" . number_format($expected, 2) . ").");
                }
            }
        });
    }
}
