<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Treatment;
use App\Services\AuditLogger;
use App\Services\PatientNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices and billing ledger.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Invoice::class);
        $user = Auth::user();

        // 1. Patient View
        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();

            $invoices = [];
            $metrics = [
                'total_billed' => 0,
                'total_paid' => 0,
                'total_balance' => 0,
            ];

            if ($patient) {
                $invoices = Invoice::with(['items', 'payments'])
                    ->where('patient_id', $patient->id)
                    ->orderBy('invoice_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->withQueryString();

                $metrics = [
                    'total_billed' => Invoice::where('patient_id', $patient->id)->where('status', '!=', 'cancelled')->sum('net_amount'),
                    'total_paid' => Invoice::where('patient_id', $patient->id)->where('status', '!=', 'cancelled')->sum('paid_amount'),
                    'total_balance' => Invoice::where('patient_id', $patient->id)->whereIn('status', ['unpaid', 'partially_paid'])->sum('balance'),
                ];
            }

            return Inertia::render('Billing/Index', [
                'invoices' => $invoices,
                'patient' => $patient,
                'metrics' => $metrics,
                'isPatient' => true,
            ]);
        }

        // 2. Staff / Admin / Dentist View
        $query = Invoice::with(['patient', 'payments', 'items']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('patient_id', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total_billed' => Invoice::where('status', '!=', 'cancelled')->sum('net_amount'),
            'total_collected' => Invoice::where('status', '!=', 'cancelled')->sum('paid_amount'),
            'total_outstanding' => Invoice::whereIn('status', ['unpaid', 'partially_paid'])->sum('balance'),
            'unpaid_count' => Invoice::whereIn('status', ['unpaid', 'partially_paid'])->count(),
        ];

        $patients = Patient::where('status', '!=', 'Archived')
            ->orderBy('last_name')
            ->get(['id', 'patient_id', 'first_name', 'last_name', 'contact_number']);

        return Inertia::render('Billing/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status', 'patient_id', 'date_from', 'date_to']),
            'metrics' => $metrics,
            'patients' => $patients,
            'isPatient' => false,
        ]);
    }

    /**
     * Show form for creating a new invoice.
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Invoice::class);

        $patients = Patient::where('status', '!=', 'Archived')
            ->orderBy('last_name')
            ->get(['id', 'patient_id', 'first_name', 'last_name', 'contact_number']);

        $preselectedTreatment = null;
        if ($request->filled('treatment_id')) {
            $preselectedTreatment = Treatment::with('patient')->find($request->treatment_id);
        }

        $preselectedAppointment = null;
        if ($request->filled('appointment_id')) {
            $preselectedAppointment = Appointment::with(['patient', 'treatment', 'user.patient'])->find($request->appointment_id);
            if (!$preselectedTreatment && $preselectedAppointment?->treatment) {
                $preselectedTreatment = $preselectedAppointment->treatment;
            }
        }

        $patientId = $request->query('patient_id');
        if (!$patientId) {
            $patientId = $preselectedTreatment?->patient_id 
                ?? $preselectedAppointment?->patient_id 
                ?? $preselectedAppointment?->user?->patient?->id;
        }

        // Generate next sequential invoice number: INV-YYYYMM-XXXX
        $countThisMonth = Invoice::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
        $nextInvoiceNumber = 'INV-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);

        return Inertia::render('Billing/Create', [
            'patients' => $patients,
            'preselected_patient_id' => $patientId ? (int)$patientId : null,
            'preselected_treatment' => $preselectedTreatment,
            'preselected_appointment' => $preselectedAppointment,
            'next_invoice_number' => $nextInvoiceNumber,
            'today' => now()->toDateString(),
            'services' => Service::active()->ordered()->get(['id', 'name', 'title', 'base_price', 'price_display']),
        ]);
    }

    /**
     * Store a newly created invoice and its items.
     */
    public function store(StoreInvoiceRequest $request)
    {
        Gate::authorize('create', Invoice::class);

        return DB::transaction(function () use ($request) {
            // Ensure unique invoice number
            $countThisMonth = Invoice::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count() + 1;
            $invoiceNumber = 'INV-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);

            while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $countThisMonth++;
                $invoiceNumber = 'INV-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);
            }

            $appointmentId = !empty($request->appointment_id) ? (int)$request->appointment_id : null;
            $treatmentId = !empty($request->treatment_id) ? (int)$request->treatment_id : null;

            if (!$appointmentId && $treatmentId) {
                $linkedTreatment = Treatment::find($treatmentId);
                if ($linkedTreatment && $linkedTreatment->appointment_id) {
                    $appointmentId = (int)$linkedTreatment->appointment_id;
                }
            } elseif (!$treatmentId && $appointmentId) {
                $linkedAppointment = Appointment::find($appointmentId);
                if ($linkedAppointment && $linkedAppointment->treatment) {
                    $treatmentId = (int)$linkedAppointment->treatment->id;
                }
            }

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'patient_id' => (int)$request->patient_id,
                'appointment_id' => $appointmentId,
                'treatment_id' => $treatmentId,
                'invoice_date' => $request->invoice_date,
                'due_date' => !empty($request->due_date) ? $request->due_date : null,
                'discount' => (float)($request->discount ?? 0),
                'tax' => (float)($request->tax ?? 0),
                'notes' => $request->notes,
                'status' => 'unpaid',
            ]);

            foreach ($request->items as $item) {
                $qty = max(1, (int)($item['quantity'] ?? 1));
                $unitPrice = max(0, (float)($item['unit_price'] ?? 0));
                $total = $qty * $unitPrice;

                $invoice->items()->create([
                    'treatment_id' => !empty($item['treatment_id']) ? (int)$item['treatment_id'] : null,
                    'description' => trim($item['description']),
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ]);
            }

            $invoice->recalculate();

            // Audit log invoice generation
            AuditLogger::log(
                module: 'billing',
                action: 'created',
                description: "Generated Invoice #{$invoice->invoice_number} for ₱" . number_format($invoice->total_amount, 2) . " (Patient ID #{$invoice->patient_id}).",
                model: $invoice,
                newValues: $invoice->only(['invoice_number', 'patient_id', 'total_amount', 'balance_due', 'status'])
            );

            // Notify patient of new billing statement
            $invoice->load('patient.user');
            PatientNotificationService::notifyInvoiceCreated($invoice);

            return redirect()->route('billing.show', $invoice->id)
                ->with('success', 'Invoice #' . $invoice->invoice_number . ' created successfully.');
        });
    }

    /**
     * Resolve invoice if route model binding received an uninstantiated model.
     */
    protected function resolveInvoice(Invoice $invoice): Invoice
    {
        if (!$invoice->exists) {
            $id = request()->route('invoice') ?? request()->route('billing');
            if ($id) {
                return Invoice::findOrFail($id);
            }
        }
        return $invoice;
    }

    /**
     * Display the specified invoice (Printable receipt / detail view).
     */
    public function show(Invoice $invoice)
    {
        $invoice = $this->resolveInvoice($invoice);
        Gate::authorize('view', $invoice);

        $invoice->load([
            'patient.medicalHistory',
            'appointment.dentist',
            'treatment.dentist',
            'items',
            'payments.receiver',
        ]);

        return Inertia::render('Billing/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Show form for editing an invoice.
     */
    public function edit(Invoice $invoice)
    {
        $invoice = $this->resolveInvoice($invoice);
        Gate::authorize('update', $invoice);

        $invoice->load(['patient', 'items']);

        return Inertia::render('Billing/Edit', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $invoice = $this->resolveInvoice($invoice);
        Gate::authorize('update', $invoice);

        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:unpaid,partially_paid,paid,cancelled',
        ]);

        $oldValues = $invoice->only(['due_date', 'discount', 'tax', 'status', 'notes', 'total_amount', 'balance_due']);
        $invoice->update($validated);
        $invoice->recalculate();

        AuditLogger::log(
            module: 'billing',
            action: 'updated',
            description: "Updated Invoice #{$invoice->invoice_number}. Status: {$invoice->status}, Total: ₱" . number_format($invoice->total_amount, 2) . ".",
            model: $invoice,
            oldValues: $oldValues,
            newValues: $invoice->only(['due_date', 'discount', 'tax', 'status', 'notes', 'total_amount', 'balance_due'])
        );

        return redirect()->route('billing.show', $invoice->id)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice = $this->resolveInvoice($invoice);
        Gate::authorize('delete', $invoice);

        AuditLogger::log(
            module: 'billing',
            action: 'deleted',
            description: "Deleted Invoice #{$invoice->invoice_number} (₱" . number_format($invoice->total_amount, 2) . ").",
            model: $invoice,
            oldValues: $invoice->only(['invoice_number', 'patient_id', 'total_amount', 'status'])
        );

        $invoice->delete();

        return redirect()->route('billing.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}
