<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first() ?? ($user->hasVerifiedEmail() ? $user->ensurePatientProfile() : null);

            // Fetch next upcoming appointment (only pending or confirmed)
            $nextAppointment = Appointment::where(function ($q) use ($user, $patient) {
                    $q->where('user_id', $user->id);
                    if ($patient) {
                        $q->orWhere('patient_id', $patient->id);
                    }
                })
                ->where('appointment_date', '>=', now()->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc')
                ->with('dentist')
                ->first();

            // Fetch total appointments
            $appointmentsCount = Appointment::where(function ($q) use ($user, $patient) {
                $q->where('user_id', $user->id);
                if ($patient) {
                    $q->orWhere('patient_id', $patient->id);
                }
            })->count();

            // Fetch real latest treatment
            $recentTreatmentModel = $patient
                ? Treatment::where('patient_id', $patient->id)
                    ->with('dentist')
                    ->orderBy('treatment_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first()
                : null;

            $recentTreatment = null;
            if ($recentTreatmentModel) {
                $recentTreatment = [
                    'id' => $recentTreatmentModel->id,
                    'date' => \Carbon\Carbon::parse($recentTreatmentModel->treatment_date)->format('M d, Y'),
                    'service' => $recentTreatmentModel->procedure_name,
                    'dentist' => $recentTreatmentModel->dentist ? $recentTreatmentModel->dentist->name : 'Clinic Dentist',
                    'status' => ucfirst(str_replace('_', ' ', $recentTreatmentModel->status)),
                    'tooth_number' => $recentTreatmentModel->tooth_number,
                ];
            }

            // Real outstanding balance from active invoices
            $outstandingBalance = $patient
                ? (float) Invoice::where('patient_id', $patient->id)
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->sum('balance')
                : 0.0;

            // Medical clearance safety protocol status
            $medicalClearance = null;
            if ($patient) {
                $patient->loadMissing('medicalHistory');
                $mh = $patient->medicalHistory;
                if ($mh && $mh->hasConditions()) {
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

                    // Deliver notification reminder if not presented
                    if (!$mh->medical_clearance_presented) {
                        \App\Services\PatientNotificationService::notifyMedicalClearanceRequired($patient, $nextAppointment);
                    }
                }
            }

            // Fetch latest notifications for patient dashboard
            $notifications = $user->notifications()->take(5)->get();
            $unreadNotificationsCount = $user->unreadNotifications()->count();

            return Inertia::render('PatientDashboard', [
                'nextAppointment' => $nextAppointment,
                'appointmentsCount' => $appointmentsCount,
                'outstandingBalance' => $outstandingBalance,
                'recentTreatment' => $recentTreatment,
                'notifications' => $notifications,
                'unreadNotificationsCount' => $unreadNotificationsCount,
                'medicalClearance' => $medicalClearance,
            ]);
        }

        // For Admin, Dentist, Staff, Secretary
        $todayStr = now()->toDateString();

        $stats = [
            'total_patients' => Patient::where('status', '!=', 'Archived')->count(),
            'today_appointments_count' => Appointment::where('appointment_date', $todayStr)->count(),
            'pending_count' => Appointment::where('status', 'pending')->count(),
            'confirmed_today_count' => Appointment::where('appointment_date', $todayStr)->where('status', 'confirmed')->count(),
            'total_receivables' => (float) Invoice::whereIn('status', ['unpaid', 'partially_paid'])->sum('balance'),
            'total_collected' => (float) Payment::sum('amount'),
            'today_collected' => (float) Payment::whereDate('created_at', $todayStr)->sum('amount'),
            'unpaid_invoices_count' => Invoice::whereIn('status', ['unpaid', 'partially_paid'])->count(),
            'total_treatments' => Treatment::count(),
            'upcoming_week_count' => Appointment::whereBetween('appointment_date', [$todayStr, now()->addDays(7)->toDateString()])->count(),
        ];

        if ($user->role === 'dentist') {
            $stats['dentist_today_appointments'] = Appointment::where('dentist_id', $user->id)->where('appointment_date', $todayStr)->count();
            $stats['dentist_confirmed_today'] = Appointment::where('dentist_id', $user->id)->where('appointment_date', $todayStr)->where('status', 'confirmed')->count();
            $stats['dentist_treatments_count'] = Treatment::where('dentist_id', $user->id)->count();
            $stats['dentist_pending_count'] = Appointment::where('dentist_id', $user->id)->where('status', 'pending')->count();
        }

        $todayAppointmentsQuery = Appointment::with(['patient', 'user', 'dentist'])
            ->where('appointment_date', $todayStr)
            ->orderBy('appointment_time', 'asc');

        if ($user->role === 'dentist') {
            $hasDentistAppts = (clone $todayAppointmentsQuery)->where('dentist_id', $user->id)->exists();
            $todayAppointments = $hasDentistAppts
                ? (clone $todayAppointmentsQuery)->where('dentist_id', $user->id)->take(8)->get()
                : $todayAppointmentsQuery->take(8)->get();
        } else {
            $todayAppointments = $todayAppointmentsQuery->take(8)->get();
        }

        $recentPendingQuery = Appointment::with(['patient', 'user', 'dentist'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        if ($user->role === 'dentist') {
            $hasPendingForDentist = (clone $recentPendingQuery)->where('dentist_id', $user->id)->exists();
            $recentPending = $hasPendingForDentist
                ? (clone $recentPendingQuery)->where('dentist_id', $user->id)->take(6)->get()
                : $recentPendingQuery->take(6)->get();
        } else {
            $recentPending = $recentPendingQuery->take(6)->get();
        }

        // Dynamic Recent Patients (For Dentists: patients treated or appointed by this dentist, or clinic recent)
        if ($user->role === 'dentist') {
            $dentistPatientIds = Appointment::where('dentist_id', $user->id)
                ->whereNotNull('patient_id')
                ->pluck('patient_id')
                ->merge(
                    Treatment::where('dentist_id', $user->id)
                        ->whereNotNull('patient_id')
                        ->pluck('patient_id')
                )
                ->unique();

            if ($dentistPatientIds->isNotEmpty()) {
                $recentPatientsQuery = Patient::whereIn('id', $dentistPatientIds)
                    ->where('status', '!=', 'Archived')
                    ->latest('updated_at');
            } else {
                $recentPatientsQuery = Patient::where('status', '!=', 'Archived')
                    ->latest('created_at');
            }
        } else {
            $recentPatientsQuery = Patient::where('status', '!=', 'Archived')
                ->latest('created_at');
        }

        $recentPatients = $recentPatientsQuery
            ->with([
                'appointments' => fn($q) => $q->latest('appointment_date')->latest('appointment_time'),
                'treatments' => fn($q) => $q->latest('treatment_date')->latest('id'),
            ])
            ->take(6)
            ->get()
            ->map(function ($p) {
                $first = $p->first_name ?: 'P';
                $last = $p->last_name ?: 'U';
                $initials = strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));

                $latestTreatment = $p->treatments->first();
                $latestAppointment = $p->appointments->first();

                if ($latestTreatment) {
                    $subtitle = $latestTreatment->procedure_name;
                    $badge = 'Treated';
                } elseif ($latestAppointment) {
                    $subtitle = $latestAppointment->service;
                    $badge = ucfirst($latestAppointment->status);
                } else {
                    $subtitle = 'Registered Patient';
                    $badge = 'New';
                }

                return [
                    'id' => $p->id,
                    'patient_id' => $p->patient_id,
                    'name' => $p->full_name,
                    'initials' => $initials,
                    'subtitle' => $subtitle,
                    'badge' => $badge,
                    'gender' => $p->gender,
                    'age' => $p->age,
                    'status' => $p->status,
                    'created_at_human' => $p->created_at ? $p->created_at->diffForHumans() : null,
                ];
            });

        // Dynamic Recent Activities (aggregated across Patients, Treatments, Payments, Appointments)
        $activities = collect();

        // 1. Newly created patients
        $latestPatients = Patient::where('status', '!=', 'Archived')
            ->latest('created_at')
            ->take(6)
            ->get();

        foreach ($latestPatients as $p) {
            if (!$p->created_at) continue;
            $activities->push([
                'id' => 'patient-' . $p->id,
                'type' => 'patient',
                'actor' => $p->user_id ? 'Patient' : 'Staff',
                'action' => $p->user_id ? 'registered an account' : 'added a new patient',
                'detail' => $p->full_name . ($p->patient_id ? " ({$p->patient_id})" : ''),
                'link' => route('patients.show', $p->id),
                'icon' => 'pi pi-user',
                'color' => 'teal',
                'created_at' => $p->created_at,
                'time_ago' => $p->created_at->diffForHumans(),
            ]);
        }

        // 2. Treatments recorded
        $treatmentsQuery = Treatment::with(['patient', 'dentist']);
        if ($user->role === 'dentist') {
            $dentistTreatments = (clone $treatmentsQuery)->where('dentist_id', $user->id)->latest('created_at')->take(6)->get();
            $latestTreatments = $dentistTreatments->isNotEmpty()
                ? $dentistTreatments
                : $treatmentsQuery->latest('created_at')->take(6)->get();
        } else {
            $latestTreatments = $treatmentsQuery->latest('created_at')->take(6)->get();
        }

        foreach ($latestTreatments as $t) {
            if (!$t->created_at) continue;
            $dentistName = $t->dentist ? $t->dentist->name : 'Dentist';
            $patientName = $t->patient ? $t->patient->full_name : 'Patient';
            $detail = $t->procedure_name . ($patientName ? ' • ' . $patientName : '');
            if ($t->tooth_number) {
                $detail .= ' (Tooth #' . $t->tooth_number . ')';
            }

            $activities->push([
                'id' => 'treatment-' . $t->id,
                'type' => 'treatment',
                'actor' => $dentistName,
                'action' => 'recorded a treatment',
                'detail' => $detail,
                'link' => route('treatments.show', $t->id),
                'icon' => 'pi pi-check-circle',
                'color' => 'blue',
                'created_at' => $t->created_at,
                'time_ago' => $t->created_at->diffForHumans(),
            ]);
        }

        // 3. Payments recorded
        $latestPayments = Payment::with(['patient', 'receiver', 'invoice'])
            ->latest('created_at')
            ->take(6)
            ->get();

        foreach ($latestPayments as $pay) {
            if (!$pay->created_at) continue;
            $receiverName = $pay->receiver ? $pay->receiver->name : 'Staff';
            $patientName = $pay->patient ? $pay->patient->full_name : 'Patient';
            $amountFormatted = '₱' . number_format((float) $pay->amount, 2);
            $detail = $amountFormatted . ($patientName ? ' • ' . $patientName : '') . ($pay->payment_method ? ' (' . $pay->payment_method . ')' : '');

            $activities->push([
                'id' => 'payment-' . $pay->id,
                'type' => 'payment',
                'actor' => $receiverName,
                'action' => 'recorded a payment',
                'detail' => $detail,
                'link' => route('payments.index'),
                'icon' => 'pi pi-wallet',
                'color' => 'green',
                'created_at' => $pay->created_at,
                'time_ago' => $pay->created_at->diffForHumans(),
            ]);
        }

        // 4. Appointments booked / scheduled
        $appointmentsQuery = Appointment::with(['patient', 'user', 'dentist']);
        if ($user->role === 'dentist') {
            $dentistAppts = (clone $appointmentsQuery)->where('dentist_id', $user->id)->latest('created_at')->take(6)->get();
            $latestAppts = $dentistAppts->isNotEmpty()
                ? $dentistAppts
                : $appointmentsQuery->latest('created_at')->take(6)->get();
        } else {
            $latestAppts = $appointmentsQuery->latest('created_at')->take(6)->get();
        }

        foreach ($latestAppts as $app) {
            if (!$app->created_at) continue;
            $actorName = $app->user ? $app->user->name : ($app->patient ? $app->patient->full_name : 'Patient');
            $dateFormatted = $app->appointment_date ? \Carbon\Carbon::parse($app->appointment_date)->format('M d, Y') : null;
            $detail = $app->service . ($dateFormatted ? ' • ' . $dateFormatted : '');

            $activities->push([
                'id' => 'appointment-' . $app->id,
                'type' => 'appointment',
                'actor' => $actorName,
                'action' => 'scheduled an appointment',
                'detail' => $detail,
                'link' => route('appointments.index'),
                'icon' => 'pi pi-calendar',
                'color' => 'amber',
                'created_at' => $app->created_at,
                'time_ago' => $app->created_at->diffForHumans(),
            ]);
        }

        // Sort descending by created_at and pick top 6
        $recentActivities = $activities
            ->filter(fn($a) => !empty($a['created_at']))
            ->sortByDesc(fn($a) => $a['created_at'] instanceof \Carbon\Carbon ? $a['created_at']->timestamp : strtotime((string) $a['created_at']))
            ->take(6)
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'todayAppointments' => $todayAppointments,
            'recentPending' => $recentPending,
            'recentPatients' => $recentPatients,
            'recentActivities' => $recentActivities,
        ]);
    }
}
