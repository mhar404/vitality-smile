<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Patient::class);
        if ($request->user()->role === 'patient') {
            // Patient user should only see their own profile
            $query = Patient::where('user_id', $request->user()->id);
        } else {
            $query = Patient::query();
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('patient_id', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('gender') && !empty($request->gender)) {
            $query->where('gender', $request->gender);
        }

        $query->with([
            'medicalHistory' => fn($q) => $q->select('id', 'patient_id', 'blood_type', 'has_allergies', 'allergies', 'hypertension', 'diabetes', 'heart_disease', 'bleeding_disorders', 'asthma', 'is_pregnant'),
            'appointments' => fn($q) => $q->latest('appointment_date')->latest('appointment_time')->take(1),
            'treatments' => fn($q) => $q->latest('treatment_date')->latest('id')->take(1),
        ]);

        $patients = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        $stats = [
            'total' => Patient::where('status', '!=', 'Archived')->count(),
            'active' => Patient::where('status', 'Active')->count(),
            'inactive' => Patient::where('status', 'Inactive')->count(),
            'archived' => Patient::where('status', 'Archived')->count(),
            'new_this_month' => Patient::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', '!=', 'Archived')->count(),
            'with_alerts' => Patient::whereHas('medicalHistory', function($q) {
                $q->where('has_allergies', true)
                  ->orWhere('hypertension', true)
                  ->orWhere('diabetes', true)
                  ->orWhere('heart_disease', true)
                  ->orWhere('bleeding_disorders', true);
            })->count(),
        ];

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search', 'status', 'gender']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Patient::class);

        $countThisMonth = Patient::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
        $suggestedId = 'PAT-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);

        while (Patient::where('patient_id', $suggestedId)->exists()) {
            $countThisMonth++;
            $suggestedId = 'PAT-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);
        }

        return Inertia::render('Patients/Create', [
            'suggested_patient_id' => $suggestedId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request)
    {
        Gate::authorize('create', Patient::class);
        $patient = Patient::create($request->validated());

        AuditLogger::log(
            module: 'patients',
            action: 'created',
            description: "Registered new patient record for {$patient->first_name} {$patient->last_name} ({$patient->patient_id}).",
            model: $patient,
            newValues: $patient->only(['patient_id', 'first_name', 'last_name', 'email', 'contact_number', 'status'])
        );

        return redirect()->route('patients.index')->with('success', 'Patient created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        Gate::authorize('view', $patient);
        $patient->load(['appointments.dentist', 'treatments.dentist', 'treatments.appointment', 'user', 'medicalHistory', 'invoices.payments', 'invoices.items']);
        return Inertia::render('Patients/Show', [
            'patient' => $patient,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Patient $patient)
    {
        Gate::authorize('update', $patient);
        return Inertia::render('Patients/Edit', [
            'patient' => $patient,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        Gate::authorize('update', $patient);
        $oldValues = $patient->only(['first_name', 'last_name', 'email', 'contact_number', 'status', 'address']);
        $patient->update($request->validated());

        AuditLogger::log(
            module: 'patients',
            action: 'updated',
            description: "Updated profile details for patient {$patient->first_name} {$patient->last_name} ({$patient->patient_id}).",
            model: $patient,
            oldValues: $oldValues,
            newValues: $patient->only(['first_name', 'last_name', 'email', 'contact_number', 'status', 'address'])
        );

        return redirect()->route('patients.index')->with('success', 'Patient updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        Gate::authorize('delete', $patient);
        $patient->update(['status' => 'Archived']);

        AuditLogger::log(
            module: 'patients',
            action: 'archived',
            description: "Archived patient record for {$patient->first_name} {$patient->last_name} ({$patient->patient_id}).",
            model: $patient,
            oldValues: ['status' => 'Active'],
            newValues: ['status' => 'Archived']
        );

        return redirect()->route('patients.index')->with('success', 'Patient archived successfully.');
    }
}
