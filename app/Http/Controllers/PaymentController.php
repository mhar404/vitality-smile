<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\AuditLogger;
use App\Services\PatientNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments / official receipts.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Patient View
        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();

            $payments = [];
            if ($patient) {
                $payments = Payment::with(['invoice.items.treatment', 'invoice.treatment', 'invoice.appointment', 'receiver'])
                    ->where('patient_id', $patient->id)
                    ->orderBy('payment_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->withQueryString();
            }

            return Inertia::render('Payments/Index', [
                'payments' => $payments,
                'patient' => $patient,
                'isPatient' => true,
            ]);
        }

        // 2. Staff / Admin / Dentist View
        $query = Payment::with(['invoice.items.treatment', 'invoice.treatment', 'invoice.appointment', 'patient', 'receiver']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('patient_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('invoice', function ($iq) use ($search) {
                      $iq->where('invoice_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $todayStr = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();

        $metrics = [
            'total_collected' => Payment::sum('amount'),
            'today_collected' => Payment::whereDate('payment_date', $todayStr)->sum('amount'),
            'month_collected' => Payment::whereDate('payment_date', '>=', $startOfMonth)->sum('amount'),
            'total_transactions' => Payment::count(),
        ];

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'payment_method', 'date_from', 'date_to']),
            'metrics' => $metrics,
            'isPatient' => false,
        ]);
    }

    /**
     * Store a payment against a specific invoice.
     */
    public function store(StorePaymentRequest $request, Invoice $invoice)
    {
        $user = Auth::user();
        if ($user->role === 'staff' || !in_array($user->role, ['admin', 'secretary', 'dentist'])) {
            abort(403, 'Unauthorized action.');
        }

        // Generate payment receipt number: REC-YYYYMM-XXXX
        $countThisMonth = Payment::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
        $paymentNumber = 'REC-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);

        while (Payment::where('payment_number', $paymentNumber)->exists()) {
            $countThisMonth++;
            $paymentNumber = 'REC-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);
        }

        $amount = (float) $request->amount;
        $amountTendered = $request->filled('amount_tendered') ? (float) $request->amount_tendered : $amount;
        $changeAmount = $request->filled('change_amount') ? (float) $request->change_amount : max(0, round($amountTendered - $amount, 2));

        $payment = $invoice->payments()->create([
            'payment_number' => $paymentNumber,
            'patient_id' => $invoice->patient_id,
            'amount' => $amount,
            'amount_tendered' => $amountTendered,
            'change_amount' => $changeAmount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'received_by' => $user->id,
            'notes' => $request->notes,
        ]);

        $invoice->recalculate();

        // Audit log payment receipt
        AuditLogger::log(
            module: 'payments',
            action: 'created',
            description: "Recorded payment #{$payment->payment_number} of ₱" . number_format($payment->amount, 2) . " via {$payment->payment_method} for Invoice #{$invoice->invoice_number}.",
            model: $payment,
            newValues: $payment->only(['payment_number', 'amount', 'payment_method', 'reference_number', 'payment_date'])
        );

        // Notify patient of payment receipt
        $payment->load(['patient.user', 'invoice.patient.user']);
        PatientNotificationService::notifyPaymentReceived($payment);

        return back()->with('success', 'Payment of ₱' . number_format($payment->amount, 2) . ' recorded successfully.');
    }

    /**
     * Display a standalone printable official payment receipt.
     */
    public function receipt(Payment $payment)
    {
        $user = Auth::user();

        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient || $payment->patient_id !== $patient->id) {
                abort(403, 'Unauthorized access to payment receipt.');
            }
        }

        $payment->load([
            'patient',
            'invoice.items.treatment',
            'invoice.treatment.dentist',
            'invoice.appointment.dentist',
            'receiver',
        ]);

        return Inertia::render('Payments/Receipt', [
            'payment' => $payment,
        ]);
    }

    /**
     * Remove a payment.
     */
    public function destroy(Payment $payment)
    {
        $user = Auth::user();
        if ($user->role === 'staff' || !in_array($user->role, ['admin', 'secretary', 'dentist'])) {
            abort(403, 'Unauthorized action.');
        }

        $invoice = $payment->invoice;

        AuditLogger::log(
            module: 'payments',
            action: 'deleted',
            description: "Deleted payment receipt #{$payment->payment_number} of ₱" . number_format($payment->amount, 2) . " for Invoice #" . ($invoice?->invoice_number ?? 'N/A') . ".",
            model: $payment,
            oldValues: $payment->only(['payment_number', 'amount', 'payment_method', 'payment_date'])
        );

        $payment->delete();

        if ($invoice) {
            $invoice->recalculate();
        }

        return back()->with('success', 'Payment receipt removed and invoice balance updated.');
    }
}
