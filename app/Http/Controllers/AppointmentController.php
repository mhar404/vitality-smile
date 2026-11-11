<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PatientNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    /**
     * Appointment date validation rules (Monday - Friday only).
     */
    protected function appointmentDateRule(bool $checkFuture = false): array
    {
        $rules = ['required', 'date'];
        if ($checkFuture) {
            $rules[] = 'after_or_equal:today';
        }
        $rules[] = function ($attribute, $value, $fail) {
            try {
                $date = Carbon::parse($value);
                if ($date->isWeekend()) {
                    $fail('Appointments are only available from Monday to Friday. The clinic is closed on weekends.');
                }
            } catch (\Exception $e) {
                $fail('Invalid appointment date format.');
            }
        };

        return $rules;
    }

    /**
     * Appointment time validation rules (9:00 AM - 6:00 PM only).
     */
    protected function appointmentTimeRule(): array
    {
        return [
            'required',
            function ($attribute, $value, $fail) {
                try {
                    $time = Carbon::parse($value)->format('H:i');
                    if ($time < '09:00' || $time > '18:00') {
                        $fail('Appointment time must be between 9:00 AM and 6:00 PM.');
                    }
                } catch (\Exception $e) {
                    $fail('Invalid appointment time format.');
                }
            },
        ];
    }

    /**
     * Services catalog list preserved from existing system.
     */
    protected array $services = [
        'Teeth Whitening - 2 Cycles (₱8,000-10,000)',
        'Odontectomy/Surgery (Impacted) (₱8,000 - ₱20,000)',
        'Wisdom Tooth Removal (Erupted) (₱5,000-₱8,000)',
        'Root Canal Treatment (₱8,000 per canal)',
        'Plastic Jacket Crown (₱5,000 - ₱6,000 per unit/tooth)',
        'Porcelein fused to Metal Jacket Crown (₱7,000 - ₱8,000 per unit/tooth)',
        'Fixed Bridge (₱7,000 - ₱8,000 per unit/tooth)',
        'Ordinary Dentures Japan Plastic Teeth',
        'Tooth Extraction (₱800-₱1,000)',
        'Oral Prophylaxis (₱800-₱1,000)',
        'Tooth Restoration (₱800-₱1,000)',
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
     * Retrieve detailed active services models.
     */
    protected function getDetailedServices()
    {
        return Service::active()->ordered()->get([
            'id',
            'name',
            'title',
            'category',
            'base_price',
            'max_price',
            'price_display',
            'duration',
            'duration_minutes',
            'icon',
            'description',
        ]);
    }

    /**
     * Resolve procedure duration in minutes.
     */
    public function resolveServiceDurationMinutes(?string $serviceName): int
    {
        if (empty($serviceName)) {
            return 60;
        }

        $service = Service::where('name', $serviceName)
            ->orWhere('title', $serviceName)
            ->first();

        if ($service && !empty($service->duration_minutes)) {
            return (int) $service->duration_minutes;
        }

        $lower = strtolower($serviceName);
        if (str_contains($lower, 'orthodontic') || str_contains($lower, 'adjustment')) {
            return 30;
        }
        if (str_contains($lower, 'restoration') || str_contains($lower, 'composite') || str_contains($lower, 'filling') || str_contains($lower, 'extraction')) {
            return 45;
        }
        if (str_contains($lower, 'odontectomy') || str_contains($lower, 'impacted') || str_contains($lower, 'root canal')) {
            return 90;
        }

        return 60;
    }

    /**
     * Retrieve the clinic's sole dentist (Dr. Valdez).
     */
    protected function getClinicDentist(): ?User
    {
        return User::where('role', 'dentist')->first() ?? User::where('role', 'admin')->first();
    }

    /**
     * Display a listing of appointments.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Patient view: only their personal appointments
        if ($user->role === 'patient') {
            $patientRecord = Patient::with('medicalHistory')->where('user_id', $user->id)->first() ?? ($user->hasVerifiedEmail() ? $user->ensurePatientProfile() : null);

            $appointments = Appointment::where(function ($q) use ($user, $patientRecord) {
                    $q->where('user_id', $user->id);
                    if ($patientRecord) {
                        $q->orWhere('patient_id', $patientRecord->id);
                    }
                })
                ->with(['dentist', 'patient.medicalHistory'])
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->paginate(10)
                ->withQueryString();

            $medicalClearance = null;
            if ($patientRecord && $patientRecord->medicalHistory && $patientRecord->medicalHistory->hasConditions()) {
                $mh = $patientRecord->medicalHistory;
                $conditions = [];
                if ($mh->hypertension) $conditions[] = 'Hypertension';
                if ($mh->diabetes) $conditions[] = 'Diabetes';
                if ($mh->heart_disease) $conditions[] = 'Heart Disease';
                if ($mh->bleeding_disorders) $conditions[] = 'Bleeding Disorders';
                if ($mh->asthma) $conditions[] = 'Asthma';
                if ($mh->kidney_disease) $conditions[] = 'Kidney Disease';
                if ($mh->liver_disease) $conditions[] = 'Liver Disease';
                if ($mh->hepatitis) $conditions[] = 'Hepatitis';
                if ($mh->epilepsy) $conditions[] = 'Epilepsy';
                if ($mh->is_pregnant) $conditions[] = 'Pregnancy';
                if ($mh->has_allergies || !empty($mh->allergies)) $conditions[] = 'Drug Allergies';

                $medicalClearance = [
                    'has_conditions' => true,
                    'clearance_presented' => (bool) $mh->medical_clearance_presented,
                    'clearing_doctor' => $mh->medical_clearance_doctor,
                    'clearance_date' => $mh->medical_clearance_date ? \Carbon\Carbon::parse($mh->medical_clearance_date)->format('M d, Y') : null,
                    'clearance_notes' => $mh->medical_clearance_notes,
                    'conditions' => $conditions,
                ];
            }

            return Inertia::render('Appointments/Index', [
                'appointments' => $appointments,
                'dentist' => $this->getClinicDentist(),
                'medicalClearance' => $medicalClearance,
            ]);
        }

        // 2. Clinic staff/dentist/admin/secretary management view
        $query = Appointment::with(['user', 'patient.medicalHistory', 'dentist', 'treatment.invoice', 'invoice']);

        // Dentist specific view: filter to their assigned appointments if desired
        if ($user->role === 'dentist') {
            $query->where(function ($q) use ($user) {
                $q->where('dentist_id', $user->id)->orWhereNull('dentist_id');
            });
        }

        // Search: patient name/id, user name, service, notes
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('service', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('patient_id', 'like', "%{$search}%")
                         ->orWhere('contact_number', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Date filter
        $today = now()->toDateString();
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->where('appointment_date', $today);
                    break;
                case 'tomorrow':
                    $query->where('appointment_date', now()->addDay()->toDateString());
                    break;
                case 'this_week':
                    $query->whereBetween('appointment_date', [
                        now()->startOfWeek()->toDateString(),
                        now()->endOfWeek()->toDateString()
                    ]);
                    break;
                case 'upcoming':
                    $query->where('appointment_date', '>=', $today);
                    break;
                case 'past':
                    $query->where('appointment_date', '<', $today);
                    break;
                case 'custom':
                    if ($request->filled('specific_date')) {
                        $query->where('appointment_date', $request->specific_date);
                    }
                    break;
            }
        }

        // Order: patient who booked first is at above (created_at asc)
        $sortBy = $request->input('sort_by', 'booking_asc');

        switch ($sortBy) {
            case 'schedule_asc':
                $query->orderBy('appointment_date', 'asc')
                      ->orderBy('appointment_time', 'asc')
                      ->orderBy('created_at', 'asc')
                      ->orderBy('id', 'asc');
                break;
            case 'schedule_desc':
                $query->orderBy('appointment_date', 'desc')
                      ->orderBy('appointment_time', 'asc')
                      ->orderBy('created_at', 'asc')
                      ->orderBy('id', 'asc');
                break;
            case 'booking_desc':
                $query->orderBy('created_at', 'desc')
                      ->orderBy('id', 'desc');
                break;
            case 'booking_asc':
            default:
                // Patient who booked first is at above
                $query->orderBy('created_at', 'asc')
                      ->orderBy('id', 'asc');
                break;
        }

        $appointments = $query
            ->paginate(15)
            ->withQueryString();

        // Calculate summary counters
        $metricsQuery = Appointment::query();
        if ($user->role === 'dentist') {
            $metricsQuery->where(function ($q) use ($user) {
                $q->where('dentist_id', $user->id)->orWhereNull('dentist_id');
            });
        }

        $metrics = [
            'total_today' => (clone $metricsQuery)->where('appointment_date', $today)->count(),
            'pending' => (clone $metricsQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $metricsQuery)->where('status', 'confirmed')->count(),
            'completed' => (clone $metricsQuery)->where('status', 'completed')->count(),
        ];

        $patients = Patient::where('status', 'Active')
            ->orderBy('last_name')
            ->get(['id', 'patient_id', 'first_name', 'last_name', 'contact_number', 'date_of_birth']);

        return Inertia::render('Appointments/Manage', [
            'appointments' => $appointments,
            'filters' => $request->only(['search', 'status', 'date_filter', 'specific_date', 'sort_by']),
            'metrics' => $metrics,
            'dentist' => $this->getClinicDentist(),
            'patients' => $patients,
            'services' => $this->getServices(),
            'detailed_services' => $this->getDetailedServices(),
            'currentRole' => $user->role,
        ]);
    }

    /**
     * Show form for booking a new appointment.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        if ($user->role === 'staff') {
            abort(403, 'Staff accounts have view-only access to appointments.');
        }
        $isClinicStaff = in_array($user->role, ['admin', 'secretary', 'dentist']);

        $patients = [];
        $medicalClearance = null;

        if ($isClinicStaff) {
            $patients = Patient::with('medicalHistory')
                ->where('status', 'Active')
                ->orderBy('last_name')
                ->get(['id', 'patient_id', 'first_name', 'last_name', 'contact_number', 'user_id', 'date_of_birth']);
        } else {
            $patient = Patient::with('medicalHistory')->where('user_id', $user->id)->first();
            if ($patient && $patient->medicalHistory && $patient->medicalHistory->hasConditions()) {
                $mh = $patient->medicalHistory;
                $conditions = [];
                if ($mh->hypertension) $conditions[] = 'Hypertension';
                if ($mh->diabetes) $conditions[] = 'Diabetes';
                if ($mh->heart_disease) $conditions[] = 'Heart Disease';
                if ($mh->bleeding_disorders) $conditions[] = 'Bleeding Disorders';
                if ($mh->asthma) $conditions[] = 'Asthma';
                if ($mh->kidney_disease) $conditions[] = 'Kidney Disease';
                if ($mh->liver_disease) $conditions[] = 'Liver Disease';
                if ($mh->hepatitis) $conditions[] = 'Hepatitis';
                if ($mh->epilepsy) $conditions[] = 'Epilepsy';
                if ($mh->is_pregnant) $conditions[] = 'Pregnancy';
                if ($mh->has_allergies || !empty($mh->allergies)) $conditions[] = 'Drug Allergies';

                $medicalClearance = [
                    'has_conditions' => true,
                    'clearance_presented' => (bool) $mh->medical_clearance_presented,
                    'clearing_doctor' => $mh->medical_clearance_doctor,
                    'clearance_date' => $mh->medical_clearance_date ? \Carbon\Carbon::parse($mh->medical_clearance_date)->format('M d, Y') : null,
                    'conditions' => $conditions,
                ];
            }
        }

        return Inertia::render('Appointments/Create', [
            'services' => $this->getServices(),
            'detailed_services' => $this->getDetailedServices(),
            'patients' => $patients,
            'dentist' => $this->getClinicDentist(),
            'preselected_patient_id' => $request->query('patient_id'),
            'preselected_service' => $request->query('service'),
            'isClinicStaff' => $isClinicStaff,
            'medicalClearance' => $medicalClearance,
        ]);
    }

    /**
     * Live Dynamic Availability API for date and duration.
     * Evaluates clinic operating hours, dentist schedule, confirmed blocks, and pending requests.
     */
    public function getAvailability(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:10|max:480',
            'service' => 'nullable|string',
            'dentist_id' => 'nullable|exists:users,id',
            'exclude_id' => 'nullable|integer',
        ]);

        $dateStr = Carbon::parse($request->date)->toDateString();
        $date = Carbon::parse($dateStr);
        $isWeekend = $date->isWeekend();

        $durationMinutes = (int) ($request->duration_minutes ?: 60);
        if ($request->filled('service') && !$request->filled('duration_minutes')) {
            $durationMinutes = $this->resolveServiceDurationMinutes($request->service);
        }

        $dentist = $request->dentist_id ? User::find($request->dentist_id) : $this->getClinicDentist();
        $dentistId = $dentist?->id;

        // Clinic is closed on weekends
        if ($isWeekend) {
            return response()->json([
                'date' => $dateStr,
                'is_operating_day' => false,
                'message' => 'The clinic is closed on weekends (Saturday & Sunday). Operating hours are Monday – Friday, 9:00 AM – 6:00 PM.',
                'slots' => [],
                'booked_intervals' => [],
            ]);
        }

        // Query appointments on this date
        $query = Appointment::where('appointment_date', $dateStr)
            ->whereIn('status', ['pending', 'confirmed', 'completed']);

        if ($dentistId) {
            $query->where(function ($q) use ($dentistId) {
                $q->where('dentist_id', $dentistId)->orWhereNull('dentist_id');
            });
        }

        if ($request->filled('exclude_id')) {
            $query->where('id', '!=', $request->exclude_id);
        }

        $existingAppointments = $query->get();

        $bookedIntervals = [];
        $confirmedIntervals = [];
        $pendingIntervals = [];

        foreach ($existingAppointments as $apt) {
            $startTime = Carbon::parse($apt->appointment_time)->format('H:i:s');
            $duration = $apt->duration_minutes ?: $this->resolveServiceDurationMinutes($apt->service);
            $endTime = $apt->end_time ? Carbon::parse($apt->end_time)->format('H:i:s') : Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');

            $intervalData = [
                'id' => $apt->id,
                'service' => $apt->service,
                'start' => substr($startTime, 0, 5),
                'end' => substr($endTime, 0, 5),
                'status' => $apt->status,
                'formatted' => Carbon::parse($startTime)->format('g:i A') . ' – ' . Carbon::parse($endTime)->format('g:i A'),
            ];

            $bookedIntervals[] = $intervalData;

            if (in_array($apt->status, ['confirmed', 'completed'])) {
                $confirmedIntervals[] = $intervalData;
            } elseif ($apt->status === 'pending') {
                $pendingIntervals[] = $intervalData;
            }
        }

        // Clinic Operating Window: 09:00 to 18:00
        $startOfDay = Carbon::parse($dateStr . ' 09:00:00');
        $endOfDay = Carbon::parse($dateStr . ' 18:00:00');
        $slotMinutes = 30;

        $slots = [];
        $current = $startOfDay->copy();

        while ($current->copy()->addMinutes(15)->lte($endOfDay)) {
            $slotTimeStr = $current->format('H:i');
            $slotEndTime = $current->copy()->addMinutes($durationMinutes);
            $slotEndTimeStr = $slotEndTime->format('H:i');

            // Check if slot is already in the past (for today or past dates)
            $isPast = false;
            if ($date->isToday()) {
                if ($current->lt(now())) {
                    $isPast = true;
                }
            } elseif ($date->isPast()) {
                $isPast = true;
            }

            // Exceeds clinic operating closing time of 6:00 PM
            $exceedsClosing = $slotEndTime->gt($endOfDay);

            // Check overlap with confirmed appointments:
            // [slotTimeStr, slotEndTimeStr) overlaps with [start, end) if:
            // slotTimeStr < end AND slotEndTimeStr > start
            $overlapsConfirmed = null;
            foreach ($confirmedIntervals as $interval) {
                if ($slotTimeStr < $interval['end'] && $slotEndTimeStr > $interval['start']) {
                    $overlapsConfirmed = $interval;
                    break;
                }
            }

            // Check overlap with pending appointments:
            $pendingOverlaps = [];
            foreach ($pendingIntervals as $interval) {
                if ($slotTimeStr < $interval['end'] && $slotEndTimeStr > $interval['start']) {
                    $pendingOverlaps[] = $interval;
                }
            }

            $available = true;
            $reason = null;

            if ($isPast) {
                $available = false;
                $reason = 'Time has already passed';
            } elseif ($exceedsClosing) {
                $available = false;
                $reason = "Exceeds 6:00 PM closing (ends at {$slotEndTime->format('g:i A')})";
            } elseif ($overlapsConfirmed) {
                $available = false;
                $reason = "Reserved: Confirmed booking ({$overlapsConfirmed['formatted']})";
            }

            $slots[] = [
                'time' => $slotTimeStr,
                'end_time' => $slotEndTimeStr,
                'label' => $current->format('g:i A'),
                'end_label' => $slotEndTime->format('g:i A'),
                'available' => $available,
                'reason' => $reason,
                'has_pending' => count($pendingOverlaps) > 0,
                'pending_count' => count($pendingOverlaps),
                'conflicting_confirmed' => $overlapsConfirmed ? $overlapsConfirmed['formatted'] : null,
            ];

            $current->addMinutes($slotMinutes);
        }

        return response()->json([
            'date' => $dateStr,
            'is_operating_day' => true,
            'clinic_hours' => [
                'start' => '09:00',
                'end' => '18:00',
                'display' => '9:00 AM – 6:00 PM (Monday – Friday)',
            ],
            'duration_minutes' => $durationMinutes,
            'slots' => $slots,
            'booked_intervals' => $bookedIntervals,
            'dentist' => $dentist ? [
                'id' => $dentist->id,
                'name' => $dentist->name,
                'role' => $dentist->role,
            ] : null,
        ]);
    }

    /**
     * Store a newly created appointment with atomic concurrency locking.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role === 'staff') {
            abort(403, 'Staff accounts have view-only access to appointments.');
        }
        $isClinicStaff = in_array($user->role, ['admin', 'secretary', 'dentist']);
        $clinicDentist = $this->getClinicDentist();

        $serviceName = $request->input('service');
        $durationMinutes = $this->resolveServiceDurationMinutes($serviceName);

        $rules = [
            'service' => 'required|string|max:255',
            'appointment_date' => $this->appointmentDateRule(!$isClinicStaff),
            'appointment_time' => $this->appointmentTimeRule(),
            'notes' => 'nullable|string',
        ];

        if ($isClinicStaff) {
            $rules['patient_id'] = 'required|exists:patients,id';
            $rules['status'] = 'required|in:pending,confirmed,completed,cancelled';
        }

        $validated = $request->validate($rules);

        $startTime = Carbon::parse($request->appointment_time)->format('H:i:s');
        $endTime = Carbon::parse($startTime)->addMinutes($durationMinutes)->format('H:i:s');

        // Check if duration pushes appointment past 18:00 (6:00 PM)
        if ($endTime > '18:00:00' && $startTime <= '18:00:00') {
            throw ValidationException::withMessages([
                'appointment_time' => "The estimated duration of {$durationMinutes} minutes ends at " . Carbon::parse($endTime)->format('g:i A') . ", which exceeds clinic operating hours (closing at 6:00 PM). Please select an earlier start time."
            ]);
        }

        $targetStatus = $isClinicStaff ? ($request->status ?? 'confirmed') : 'pending';
        $dentistId = $clinicDentist?->id;

        // Concurrency-safe atomic check and insert
        $appointment = DB::transaction(function () use ($user, $isClinicStaff, $request, $dentistId, $serviceName, $durationMinutes, $startTime, $endTime, $targetStatus) {
            // Strictly check for any overlapping confirmed appointments
            $confirmedConflict = Appointment::where('appointment_date', $request->appointment_date)
                ->where('status', 'confirmed')
                ->where(function ($q) use ($dentistId) {
                    if ($dentistId) {
                        $q->where('dentist_id', $dentistId)->orWhereNull('dentist_id');
                    }
                })
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('appointment_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->lockForUpdate()
                ->first();

            if ($confirmedConflict) {
                $conflictStart = Carbon::parse($confirmedConflict->appointment_time)->format('g:i A');
                $conflictEnd = Carbon::parse($confirmedConflict->end_time ?: $confirmedConflict->appointment_time)->format('g:i A');
                throw ValidationException::withMessages([
                    'appointment_time' => "This time slot ({$startTime} – {$endTime}) is unavailable because Dr. Valdez already has a confirmed appointment from {$conflictStart} to {$conflictEnd}."
                ]);
            }

            if (!$isClinicStaff) {
                $patient = Patient::where('user_id', $user->id)->first() ?? ($user->hasVerifiedEmail() ? $user->ensurePatientProfile() : null);

                return Appointment::create([
                    'user_id' => $user->id,
                    'patient_id' => $patient?->id,
                    'dentist_id' => $dentistId,
                    'service' => $serviceName,
                    'duration_minutes' => $durationMinutes,
                    'appointment_date' => $request->appointment_date,
                    'appointment_time' => $startTime,
                    'end_time' => $endTime,
                    'notes' => $request->notes,
                    'status' => 'pending',
                ]);
            } else {
                $patient = Patient::find($request->patient_id);

                return Appointment::create([
                    'user_id' => $patient?->user_id ?? $user->id,
                    'patient_id' => $request->patient_id,
                    'dentist_id' => $dentistId,
                    'service' => $serviceName,
                    'duration_minutes' => $durationMinutes,
                    'appointment_date' => $request->appointment_date,
                    'appointment_time' => $startTime,
                    'end_time' => $endTime,
                    'notes' => $request->notes,
                    'status' => $targetStatus,
                ]);
            }
        });

        // Notify patient through registered email & app notification
        $appointment->load(['dentist', 'patient.user', 'user']);
        if ($targetStatus === 'confirmed') {
            PatientNotificationService::notifyAppointmentConfirmed($appointment);
            PatientNotificationService::notifyDentistAppointmentConfirmed($appointment);
        } else {
            PatientNotificationService::notifyAppointmentBooked($appointment);
            // Also notify clinic staff, secretary, and dentist of incoming appointment request
            PatientNotificationService::notifyStaffNewAppointment($appointment);
        }

        $startFormatted = Carbon::parse($startTime)->format('g:i A');
        $endFormatted = Carbon::parse($endTime)->format('g:i A');

        // Audit log appointment booking
        AuditLogger::log(
            module: 'appointments',
            action: $targetStatus === 'confirmed' ? 'confirmed' : 'created',
            description: "Appointment #{$appointment->id} booked for {$serviceName} on {$request->appointment_date} ({$startFormatted} – {$endFormatted}). Status: {$targetStatus}.",
            model: $appointment,
            newValues: $appointment->only(['service', 'appointment_date', 'appointment_time', 'end_time', 'duration_minutes', 'status', 'patient_id', 'dentist_id', 'notes'])
        );

        $message = !$isClinicStaff
            ? "Your appointment request has been submitted successfully! Scheduled Window: {$startFormatted} – {$endFormatted} ({$durationMinutes} mins)."
            : "Appointment successfully booked for {$startFormatted} – {$endFormatted} ({$durationMinutes} mins)!";

        return redirect()->route('appointments.index')->with('success', $message);
    }

    /**
     * Update full appointment details (reschedule, notes, service).
     */
    public function update(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        if ($user->role === 'staff') {
            abort(403, 'Staff accounts have view-only access to appointments.');
        }
        $isClinicStaff = in_array($user->role, ['admin', 'secretary', 'dentist']);

        if (!$isClinicStaff) {
            // Patient can only cancel their own appointment
            if ($appointment->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }

            $appointment->update([
                'status' => 'cancelled',
                'notes' => $request->filled('notes') ? $request->notes : $appointment->notes,
            ]);

            return back()->with('success', 'Appointment cancelled successfully.');
        }

        $validated = $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'service' => 'required|string|max:255',
            'appointment_date' => $this->appointmentDateRule(false),
            'appointment_time' => $this->appointmentTimeRule(),
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $serviceName = $request->service;
        $durationMinutes = $this->resolveServiceDurationMinutes($serviceName);
        $startTime = Carbon::parse($request->appointment_time)->format('H:i:s');
        $endTime = Carbon::parse($startTime)->addMinutes($durationMinutes)->format('H:i:s');

        if ($endTime > '18:00:00' && $startTime <= '18:00:00') {
            throw ValidationException::withMessages([
                'appointment_time' => "The estimated duration of {$durationMinutes} minutes ends at " . Carbon::parse($endTime)->format('g:i A') . ", which exceeds clinic operating hours (closing at 6:00 PM). Please select an earlier start time."
            ]);
        }

        $dentistId = $appointment->dentist_id ?: $this->getClinicDentist()?->id;

        // If target status is confirmed, check for conflicts with other confirmed appointments
        if ($request->status === 'confirmed') {
            $conflict = DB::transaction(function () use ($appointment, $request, $dentistId, $startTime, $endTime) {
                return Appointment::where('appointment_date', $request->appointment_date)
                    ->where('status', 'confirmed')
                    ->where('id', '!=', $appointment->id)
                    ->where(function ($q) use ($dentistId) {
                        if ($dentistId) {
                            $q->where('dentist_id', $dentistId)->orWhereNull('dentist_id');
                        }
                    })
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->where('appointment_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                    })
                    ->lockForUpdate()
                    ->first();
            });

            if ($conflict) {
                $conflictStart = Carbon::parse($conflict->appointment_time)->format('g:i A');
                $conflictEnd = Carbon::parse($conflict->end_time ?: $conflict->appointment_time)->format('g:i A');
                return back()->with('error', "Cannot reschedule appointment: The selected time slot overlaps with an existing confirmed appointment ({$conflictStart} – {$conflictEnd}) for Dr. Valdez.");
            }
        }

        $oldData = $appointment->only(['appointment_date', 'appointment_time', 'end_time', 'service', 'status', 'notes']);

        $validated['dentist_id'] = $dentistId;
        $validated['duration_minutes'] = $durationMinutes;
        $validated['end_time'] = $endTime;

        $appointment->update($validated);

        // Audit log rescheduling/update
        AuditLogger::log(
            module: 'appointments',
            action: 'updated',
            description: "Updated/Rescheduled appointment #{$appointment->id} for {$appointment->service} on {$appointment->appointment_date} at " . Carbon::parse($appointment->appointment_time)->format('g:i A') . ".",
            model: $appointment,
            oldValues: $oldData,
            newValues: $appointment->only(['appointment_date', 'appointment_time', 'end_time', 'service', 'status', 'notes'])
        );

        // Notify patient of schedule change
        $appointment->load(['dentist', 'patient.user', 'user']);
        PatientNotificationService::notifyAppointmentRescheduled($appointment);

        return back()->with('success', 'Appointment rescheduled and updated successfully.');
    }

    /**
     * Quick status update with Secretary availability recheck (Confirm, Complete, Cancel).
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        if ($user->role === 'staff') {
            abort(403, 'Staff accounts have view-only access to appointments.');
        }
        $isClinicStaff = in_array($user->role, ['admin', 'secretary', 'dentist']);

        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        if (!$isClinicStaff) {
            // Patient can only cancel their own appointment
            if ($appointment->user_id !== $user->id || $request->status !== 'cancelled') {
                abort(403, 'Unauthorized action.');
            }
        }

        $newStatus = $request->status;

        // Requirement 10: Recheck availability when Secretary / Staff approves a pending appointment!
        if ($newStatus === 'confirmed') {
            $duration = $appointment->duration_minutes ?: $this->resolveServiceDurationMinutes($appointment->service);
            $startTime = Carbon::parse($appointment->appointment_time)->format('H:i:s');
            $endTime = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('H:i:s') : Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');

            $conflict = DB::transaction(function () use ($appointment, $startTime, $endTime) {
                return Appointment::where('appointment_date', $appointment->appointment_date)
                    ->where('status', 'confirmed')
                    ->where('id', '!=', $appointment->id)
                    ->where(function ($q) use ($appointment) {
                        if ($appointment->dentist_id) {
                            $q->where('dentist_id', $appointment->dentist_id)->orWhereNull('dentist_id');
                        }
                    })
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->where('appointment_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                    })
                    ->lockForUpdate()
                    ->first();
            });

            if ($conflict) {
                $conflictStart = Carbon::parse($conflict->appointment_time)->format('g:i A');
                $conflictEnd = Carbon::parse($conflict->end_time ?: $conflict->appointment_time)->format('g:i A');
                return back()->with('error', "Cannot confirm appointment #{$appointment->id}: This time slot overlaps with an existing confirmed appointment ({$conflictStart} – {$conflictEnd}) for Dr. Valdez. Please reschedule before confirming.");
            }
        }

        $updateData = ['status' => $newStatus];

        if (!$appointment->dentist_id) {
            $updateData['dentist_id'] = $this->getClinicDentist()?->id;
        }

        if (empty($appointment->end_time)) {
            $duration = $appointment->duration_minutes ?: $this->resolveServiceDurationMinutes($appointment->service);
            $updateData['duration_minutes'] = $duration;
            $updateData['end_time'] = Carbon::parse($appointment->appointment_time)->addMinutes($duration)->format('H:i:s');
        }

        $oldStatus = $appointment->status;
        $appointment->update($updateData);

        // Audit log status change
        AuditLogger::log(
            module: 'appointments',
            action: $newStatus,
            description: "Updated appointment #{$appointment->id} ({$appointment->service}) status from '{$oldStatus}' to '{$newStatus}'.",
            model: $appointment,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus]
        );

        // Notify patient of status update (confirmation or cancellation)
        $appointment->load(['dentist', 'patient.user', 'user']);
        if ($newStatus === 'confirmed') {
            PatientNotificationService::notifyAppointmentConfirmed($appointment);
            PatientNotificationService::notifyDentistAppointmentConfirmed($appointment);
        } elseif ($newStatus === 'cancelled') {
            PatientNotificationService::notifyAppointmentCancelled($appointment, $request->notes ?? null);
        }

        return back()->with('success', "Appointment status updated to {$newStatus}.");
    }

    /**
     * Remove or cancel an appointment.
     */
    public function destroy(Appointment $appointment)
    {
        $user = Auth::user();
        if ($user->role === 'staff') {
            abort(403, 'Staff accounts have view-only access to appointments.');
        }
        $isClinicStaff = in_array($user->role, ['admin', 'secretary', 'dentist']);

        if (!$isClinicStaff && $appointment->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // To maintain healthcare audit history, we cancel rather than hard-delete
        $oldStatus = $appointment->status;
        $appointment->update(['status' => 'cancelled']);

        AuditLogger::log(
            module: 'appointments',
            action: 'cancelled',
            description: "Cancelled appointment #{$appointment->id} ({$appointment->service}).",
            model: $appointment,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'cancelled']
        );

        return back()->with('success', 'Appointment has been cancelled.');
    }

    /**
     * Trigger on-demand dispatch of upcoming appointment reminders.
     */
    public function sendReminders(Request $request)
    {
        $user = Auth::user();
        $isClinicStaff = in_array($user->role, ['admin', 'secretary', 'dentist', 'staff']);
        if (!$isClinicStaff) {
            abort(403, 'Unauthorized action.');
        }

        $stats = PatientNotificationService::dispatchUpcomingReminders();

        $totalSent = $stats['staff_reminders_sent'] + $stats['patient_reminders_sent'];

        return back()->with('success', "Upcoming appointment reminders scanned! Sent {$totalSent} notification(s) ({$stats['staff_reminders_sent']} staff/dentist, {$stats['patient_reminders_sent']} patient) across {$stats['appointments_processed']} approaching appointment(s).");
    }
}
