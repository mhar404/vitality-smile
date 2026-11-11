<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $invoice_number
 * @property int $patient_id
 * @property int|null $appointment_id
 * @property int|null $treatment_id
 * @property \Illuminate\Support\Carbon|string $invoice_date
 * @property \Illuminate\Support\Carbon|string|null $due_date
 * @property float|string $total_amount
 * @property float|string $discount
 * @property float|string $tax
 * @property float|string $net_amount
 * @property float|string $paid_amount
 * @property float|string $balance
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Patient $patient
 * @property-read \App\Models\Appointment|null $appointment
 * @property-read \App\Models\Treatment|null $treatment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvoiceItem> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 */
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'appointment_id',
        'treatment_id',
        'invoice_date',
        'due_date',
        'total_amount',
        'discount',
        'tax',
        'net_amount',
        'paid_amount',
        'balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
    }

    public function getDentistAttribute()
    {
        return $this->treatment?->dentist ?? $this->appointment?->dentist;
    }

    /**
     * Recalculate invoice totals, balances, and payment status.
     */
    public function recalculate(): void
    {
        $itemsTotal = (float) $this->items()->sum('total');
        $this->total_amount = $itemsTotal > 0 ? $itemsTotal : (float) $this->total_amount;
        $this->net_amount = round(max(0, (float) $this->total_amount - (float) ($this->discount ?? 0) + (float) ($this->tax ?? 0)), 2);
        
        $totalPaid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;
        $this->balance = round(max(0, (float) $this->net_amount - (float) $this->paid_amount), 2);

        if ($this->balance <= 0 && $this->net_amount > 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }
}
