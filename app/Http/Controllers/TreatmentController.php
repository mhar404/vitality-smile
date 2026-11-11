<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PatientNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    /**
     * Services catalog preserved from clinic records.
     */
    protected array $services = [
        'Oral Prophylaxis (Cleaning)',
        'Tooth Restoration (Filling)',
        'Tooth Extraction',
        'Root Canal Treatment',
        'Teeth Whitening',
        'Wisdom Tooth Removal (Erupted)',
        'Odontectomy / Surgery (Impacted)',
        'Plastic Jacket Crown',
        'Porcelain Fused to Metal Crown',
        'Fixed Bridge',
        'Ordinary Dentures (Japan Plastic Teeth)',
        'Orthodontic Consultation / Adjustment',
        'Fluoride Treatment',
        'Dental Check-up & Consultation',
    ];

    /**
     * Retrieve active services dynamically with fallback to default catalog.
     */
    protected function getServices(): array
    {
        $dbNames = Service::active()->ordered()->pluck('name')->filter()->toArray();
        $dbTitles = Service::active()->ordered()->pluck('title')->filter()->toArray();
        $merged = array_values(array_unique(array_merge($dbNames, $dbTitles)));
        return !empty($merged) ? $merged : $this->services;
    }

    /**
     * Display a listing of treatments.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Treatment::class);
        $user = Auth::user();

        // 1. Patient View: only their own treatment history
        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();

            $treatments = [];
            if ($patient) {
                $treatments = Treatment::with(['dentist', 'appointment'])
                    ->where('patient_id', $patient->id)
                    ->orderBy('treatment_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->withQueryString();
            }

            return Inertia::render('Treatments/Index', [
                'treatments' => $treatments,
                'patient' => $patient,
                'isPatient' => true,
            ]);
        }

        // 2. Clinic Staff / Dentist / Admin View
        $query = Treatment::with(['patient.medicalHistory', 'dentist', 'appointment', 'invoice']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('procedure_name', 'like', "%{$search}%")
                  ->orWhere('tooth_number', 'like', "%{$search}%")
                  ->orWhere('diagnosis', 'like', "%{$search}%")
                  ->orWhere('treatment_notes', 'like', "%{$search}%")
                  ->orWhere('prescription', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('patient_id', 'like', "%{$search}%")
                         ->orWhere('contact_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('dentist_id') && $request->dentist_id !== 'all') {
            $query->where('dentist_id', $request->dentist_id);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('treatment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('treatment_date', '<=', $request->date_to);
        }

        $treatments = $query->orderBy('treatment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total_treatments' => Treatment::count(),
            'completed' => Treatment::where('status', 'completed')->count(),
            'in_progress' => Treatment::where('status', 'in_progress')->count(),
            'total_revenue' => Treatment::where('status', 'completed')->sum('cost'),
        ];

        $dentists = User::whereIn('role', ['dentist', 'admin'])->orderBy('name')->get(['id', 'name', 'email']);
        $patients = Patient::where('status', '!=', 'Archived')
            ->orderBy('last_name')
            ->get(['id', 'patient_id', 'first_name', 'last_name', 'contact_number']);

        return Inertia::render('Treatments/Index', [
            'treatments' => $treatments,
            'filters' => $request->only(['search', 'status', 'dentist_id', 'patient_id', 'date_from', 'date_to']),
            'metrics' => $metrics,
            'dentists' => $dentists,
            'patients' => $patients,
            'services' => $this->getServices(),
            'isPatient' => false,
        ]);
    }

    /**
     * Show form for creating a new treatment record.
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Treatment::class);

        $patients = Patient::with('medicalHistory')
            ->where('status', '!=', 'Archived')
            ->orderBy('last_name')
            ->get();

        $dentists = User::whereIn('role', ['dentist', 'admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $preselectedAppointment = null;
        if ($request->filled('appointment_id')) {
            $preselectedAppointment = Appointment::with(['patient.medicalHistory', 'dentist'])->find($request->appointment_id);
        }

        $services = $this->getServices();
        $selectedProcedure = $request->query('procedure') ?? $preselectedAppointment?->service;

        if ($selectedProcedure && !in_array($selectedProcedure, $services)) {
            array_unshift($services, $selectedProcedure);
        }

        $serviceCatalog = Service::active()->ordered()->get(['id', 'name', 'title', 'category', 'base_price', 'max_price', 'price_display']);

        return Inertia::render('Treatments/Create', [
            'patients' => $patients,
            'dentists' => $dentists,
            'services' => $services,
            'serviceCatalog' => $serviceCatalog,
            'preselected_patient_id' => $request->query('patient_id', $preselectedAppointment?->patient_id),
            'preselected_appointment' => $preselectedAppointment,
            'preselected_procedure' => $selectedProcedure,
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * Store a newly created treatment.
     */
    public function store(StoreTreatmentRequest $request)
    {
        Gate::authorize('create', Treatment::class);

        $treatment = Treatment::create($request->validated());

        // Sync medical clearance status with patient's medical history record
        if ($request->boolean('medical_clearance_presented') && $treatment->patient) {
            $treatment->patient->medicalHistory()->updateOrCreate(
                ['patient_id' => $treatment->patient_id],
                [
                    'medical_clearance_presented' => true,
                    'medical_clearance_doctor' => $treatment->medical_clearance_doctor,
                    'medical_clearance_date' => $treatment->medical_clearance_date,
                    'medical_clearance_notes' => $treatment->medical_clearance_notes,
                ]
            );
        }

        // Audit log treatment documentation
        AuditLogger::log(
            module: 'treatments',
            action: 'created',
            description: "Documented treatment record #{$treatment->id} ({$treatment->procedure_name}) for patient ID #{$treatment->patient_id}." . ($treatment->medical_clearance_presented ? " [Medical Clearance Verified: Dr. {$treatment->medical_clearance_doctor}]" : ""),
            model: $treatment,
            newValues: $treatment->only(['patient_id', 'dentist_id', 'procedure_name', 'tooth_number', 'status', 'treatment_date', 'medical_clearance_presented', 'medical_clearance_doctor'])
        );

        // Notify patient of new clinical record in chart
        $treatment->load(['patient.user', 'dentist']);
        PatientNotificationService::notifyTreatmentAdded($treatment);

        // When a treatment linked to an appointment is completed, update appointment status as completed
        if ($treatment->appointment_id && $treatment->status === 'completed') {
            $appointment = Appointment::find($treatment->appointment_id);
            if ($appointment && $appointment->status !== 'completed') {
                $appointment->update(['status' => 'completed']);
            }
        }

        if ($request->input('redirect_to_patient') && $treatment->patient_id) {
            return redirect()->route('patients.show', $treatment->patient_id)
                ->with('success', 'Treatment record successfully documented.');
        }

        return redirect()->route('treatments.index')
            ->with('success', 'Treatment record successfully documented.');
    }

    /**
     * Display the specified treatment.
     */
    public function show(Treatment $treatment)
    {
        Gate::authorize('view', $treatment);

        $treatment->load(['patient', 'dentist', 'appointment']);

        return Inertia::render('Treatments/Show', [
            'treatment' => $treatment,
        ]);
    }

    /**
     * Show form for editing a treatment record.
     */
    public function edit(Treatment $treatment)
    {
        Gate::authorize('update', $treatment);

        $treatment->load(['patient.medicalHistory', 'dentist', 'appointment']);

        $patients = Patient::with('medicalHistory')
            ->where('status', '!=', 'Archived')
            ->orderBy('last_name')
            ->get();

        $dentists = User::whereIn('role', ['dentist', 'admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $serviceCatalog = Service::active()->ordered()->get(['id', 'name', 'title', 'category', 'base_price', 'max_price', 'price_display']);

        return Inertia::render('Treatments/Edit', [
            'treatment' => $treatment,
            'patients' => $patients,
            'dentists' => $dentists,
            'services' => $this->getServices(),
            'serviceCatalog' => $serviceCatalog,
        ]);
    }

    /**
     * Update the specified treatment in storage.
     */
    public function update(UpdateTreatmentRequest $request, Treatment $treatment)
    {
        Gate::authorize('update', $treatment);

        $oldValues = $treatment->only(['patient_id', 'dentist_id', 'procedure_name', 'tooth_number', 'status', 'treatment_date', 'treatment_notes', 'medical_clearance_presented', 'medical_clearance_doctor']);
        $treatment->update($request->validated());

        // Sync medical clearance status with patient's medical history record
        if ($request->boolean('medical_clearance_presented') && $treatment->patient) {
            $treatment->patient->medicalHistory()->updateOrCreate(
                ['patient_id' => $treatment->patient_id],
                [
                    'medical_clearance_presented' => true,
                    'medical_clearance_doctor' => $treatment->medical_clearance_doctor,
                    'medical_clearance_date' => $treatment->medical_clearance_date,
                    'medical_clearance_notes' => $treatment->medical_clearance_notes,
                ]
            );
        }

        AuditLogger::log(
            module: 'treatments',
            action: 'updated',
            description: "Updated treatment record #{$treatment->id} ({$treatment->procedure_name}).",
            model: $treatment,
            oldValues: $oldValues,
            newValues: $treatment->only(['patient_id', 'dentist_id', 'procedure_name', 'tooth_number', 'status', 'treatment_date', 'treatment_notes', 'medical_clearance_presented', 'medical_clearance_doctor'])
        );

        return redirect()->route('treatments.index')
            ->with('success', 'Treatment record updated successfully.');
    }

    /**
     * Remove the specified treatment from storage.
     */
    public function destroy(Treatment $treatment)
    {
        Gate::authorize('delete', $treatment);

        AuditLogger::log(
            module: 'treatments',
            action: 'deleted',
            description: "Deleted treatment record #{$treatment->id} ({$treatment->procedure_name}).",
            model: $treatment,
            oldValues: $treatment->only(['patient_id', 'procedure_name', 'treatment_date'])
        );

        $treatment->delete();

        return back()->with('success', 'Treatment record deleted successfully.');
    }
}
