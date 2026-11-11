<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'treatment_id',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected $appends = ['item_name', 'tooth_number'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function getItemNameAttribute(): string
    {
        return $this->description ?? $this->treatment?->procedure_name ?? 'Dental Service';
    }

    public function getToothNumberAttribute(): ?string
    {
        return $this->treatment?->tooth_number;
    }
}
