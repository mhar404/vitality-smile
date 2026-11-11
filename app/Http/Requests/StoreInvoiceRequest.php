<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'patient_id' => !empty($this->patient_id) ? (int)$this->patient_id : null,
            'appointment_id' => !empty($this->appointment_id) ? (int)$this->appointment_id : null,
            'treatment_id' => !empty($this->treatment_id) ? (int)$this->treatment_id : null,
            'discount' => ($this->discount !== null && $this->discount !== '') ? (float)$this->discount : 0,
            'tax' => ($this->tax !== null && $this->tax !== '') ? (float)$this->tax : 0,
            'items' => collect($this->items)->map(function ($item) {
                if (is_array($item)) {
                    $item['treatment_id'] = !empty($item['treatment_id']) ? (int)$item['treatment_id'] : null;
                    $item['quantity'] = !empty($item['quantity']) ? (int)$item['quantity'] : 1;
                    $item['unit_price'] = ($item['unit_price'] !== null && $item['unit_price'] !== '') ? (float)$item['unit_price'] : 0;
                }
                return $item;
            })->all(),
        ]);
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
            'appointment_id' => 'nullable|exists:appointments,id',
            'treatment_id' => 'nullable|exists:treatments,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.treatment_id' => 'nullable|exists:treatments,id',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Please select a patient for this invoice.',
            'patient_id.exists' => 'The selected patient does not exist.',
            'items.required' => 'Please include at least one dental service line item.',
            'items.min' => 'Please include at least one dental service line item.',
            'items.*.description.required' => 'A service description is required for every line item.',
            'items.*.quantity.required' => 'Quantity is required for every line item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Unit price is required for every line item.',
            'items.*.unit_price.min' => 'Unit price must be at least 0.',
        ];
    }
}
