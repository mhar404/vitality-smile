<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\User;
use App\Notifications\PatientActivityNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PatientNotificationService
{
    /**
     * Safely deliver notification to user via database and registered email.
     */
    public static function send(?User $user, PatientActivityNotification $notification): void
    {
        if (!$user) {
            return;
        }

        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::warning("Patient notification email delivery encountered an error for user {$user->id} ({$user->email}): " . $e->getMessage());

            // Ensure database notification is preserved even if external SMTP connection encounters an issue
            try {
                $alreadySaved = $user->notifications()
                    ->where('type', get_class($notification))
                    ->where('data->title', $notification->title)
                    ->where('created_at', '>=', now()->subSeconds(2))
                    ->exists();

                if (!$alreadySaved) {
                    $user->notifications()->create([
                        'id' => (string) Str::uuid(),
                        'type' => get_class($notification),
                        'data' => $notification->toArray($user),
                        'read_at' => null,
                    ]);
                }
            } catch (\Throwable $dbEx) {
                Log::error("Failed to fallback-save database notification: " . $dbEx->getMessage());
            }
        }
    }

    /**
     * Notify patient that their appointment booking request has been submitted.
     */
    public static function notifyAppointmentBooked(Appointment $appointment): void
    {
        $user = $appointment->user ?? $appointment->patient?->user;
        if (!$user) {
            return;
        }

        $appointment->loadMissing('patient.medicalHistory');
        $hasConditions = $appointment->patient?->medicalHistory?->hasConditions() ?? false;
        $clearancePresented = $appointment->patient?->medicalHistory?->medical_clearance_presented ?? false;

        $dateFormatted = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $startFormatted = Carbon::parse($appointment->appointment_time)->format('g:i A');
        $endFormatted = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('g:i A') : null;
        $timeWindow = $endFormatted ? "{$startFormatted} – {$endFormatted}" : $startFormatted;
        $dentistName = $appointment->dentist?->name ?? 'Dr. Valdez';

        $message = "Your appointment request for {$appointment->service} has been received and is currently pending review by the clinic staff.";
        $details = [
            'Procedure' => $appointment->service,
            'Date' => $dateFormatted,
            'Time Slot' => $timeWindow,
            'Duration' => ($appointment->duration_minutes ?: 60) . ' mins',
            'Attending Dentist' => $dentistName,
            'Booking Status' => 'Pending Clinic Review',
        ];

        if ($hasConditions && !$clearancePresented) {
            $message .= " ⚠️ Clinical Safety Notice: Our records indicate you have documented medical conditions. In accordance with clinic safety protocols, you are required to present an official Physician Medical Clearance signed by your attending doctor before the dentist can administer any treatment. Please bring your clearance letter to your appointment.";
            $details['Medical Clearance'] = 'MANDATORY: Physician Medical Clearance required prior to treatment.';
        }

        $notification = new PatientActivityNotification(
            title: "Appointment Request Submitted",
            message: $message,
            category: "appointment",
            actionUrl: "/appointments",
            actionText: "View Appointment Request",
            details: $details
        );

        self::send($user, $notification);
    }

    /**
     * Notify patient that their appointment has been confirmed by the Secretary / Staff.
     */
    public static function notifyAppointmentConfirmed(Appointment $appointment): void
    {
        $user = $appointment->user ?? $appointment->patient?->user;
        if (!$user) {
            return;
        }

        $appointment->loadMissing('patient.medicalHistory');
        $hasConditions = $appointment->patient?->medicalHistory?->hasConditions() ?? false;
        $clearancePresented = $appointment->patient?->medicalHistory?->medical_clearance_presented ?? false;

        $dateFormatted = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $startFormatted = Carbon::parse($appointment->appointment_time)->format('g:i A');
        $endFormatted = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('g:i A') : null;
        $timeWindow = $endFormatted ? "{$startFormatted} – {$endFormatted}" : $startFormatted;
        $dentistName = $appointment->dentist?->name ?? 'Dr. Valdez';

        $message = "Great news! Your dental appointment for {$appointment->service} has been officially confirmed. Please arrive 10 minutes before your scheduled time for initial screening.";
        $details = [
            'Procedure' => $appointment->service,
            'Date' => $dateFormatted,
            'Confirmed Time' => $timeWindow,
            'Duration' => ($appointment->duration_minutes ?: 60) . ' mins',
            'Attending Dentist' => $dentistName,
            'Status' => 'Confirmed',
        ];

        if ($hasConditions && !$clearancePresented) {
            $message .= " ⚠️ Action Required: Because you have documented medical conditions, you must present a valid Physician Medical Clearance signed by your attending doctor upon arrival before the dentist can proceed with your procedure.";
            $details['Medical Clearance Required'] = 'MANDATORY: Present signed Physician Medical Clearance upon arrival.';
        }

        $notification = new PatientActivityNotification(
            title: "Appointment Confirmed",
            message: $message,
            category: "appointment",
            actionUrl: "/appointments",
            actionText: "View Confirmed Schedule",
            details: $details
        );

        self::send($user, $notification);
    }

    /**
     * Notify patient that an appointment was cancelled.
     */
    public static function notifyAppointmentCancelled(Appointment $appointment, ?string $reason = null): void
    {
        $user = $appointment->user ?? $appointment->patient?->user;
        if (!$user) {
            return;
        }

        $dateFormatted = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $startFormatted = Carbon::parse($appointment->appointment_time)->format('g:i A');

        $details = [
            'Procedure' => $appointment->service,
            'Cancelled Date' => $dateFormatted,
            'Scheduled Time' => $startFormatted,
            'Status' => 'Cancelled',
        ];

        if ($reason) {
            $details['Reason / Notes'] = $reason;
        }

        $notification = new PatientActivityNotification(
            title: "Appointment Cancelled",
            message: "Your appointment for {$appointment->service} on {$dateFormatted} has been cancelled. You may schedule a new appointment anytime through our online portal.",
            category: "appointment",
            actionUrl: "/appointments/create",
            actionText: "Book New Appointment",
            details: $details
        );

        self::send($user, $notification);
    }

    /**
     * Notify patient that an appointment was rescheduled to a new date/time.
     */
    public static function notifyAppointmentRescheduled(Appointment $appointment): void
    {
        $user = $appointment->user ?? $appointment->patient?->user;
        if (!$user) {
            return;
        }

        $dateFormatted = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $startFormatted = Carbon::parse($appointment->appointment_time)->format('g:i A');
        $endFormatted = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('g:i A') : null;
        $timeWindow = $endFormatted ? "{$startFormatted} – {$endFormatted}" : $startFormatted;
        $dentistName = $appointment->dentist?->name ?? 'Dr. Valdez';

        $notification = new PatientActivityNotification(
            title: "Appointment Rescheduled",
            message: "Your appointment for {$appointment->service} has been rescheduled to {$dateFormatted} at {$timeWindow}.",
            category: "appointment",
            actionUrl: "/appointments",
            actionText: "View Updated Schedule",
            details: [
                'Procedure' => $appointment->service,
                'New Date' => $dateFormatted,
                'New Time Window' => $timeWindow,
                'Duration' => ($appointment->duration_minutes ?: 60) . ' mins',
                'Attending Dentist' => $dentistName,
                'Status' => ucfirst($appointment->status),
            ]
        );

        self::send($user, $notification);
    }

    /**
     * Notify patient that a new treatment was documented in their dental record.
     */
    public static function notifyTreatmentAdded(Treatment $treatment): void
    {
        $user = $treatment->patient?->user;
        if (!$user) {
            return;
        }

        $dentistName = $treatment->dentist?->name ?? 'Dr. Valdez';
        $dateFormatted = Carbon::parse($treatment->treatment_date)->format('l, F j, Y');
        $toothInfo = $treatment->tooth_number ? "Tooth #{$treatment->tooth_number}" : 'General / Full Mouth';

        $notification = new PatientActivityNotification(
            title: "New Dental Record Added",
            message: "Dr. {$dentistName} has added a new clinical treatment record for {$treatment->procedure_name} to your patient chart.",
            category: "treatment",
            actionUrl: "/treatments",
            actionText: "View Dental Chart",
            details: [
                'Procedure' => $treatment->procedure_name,
                'Tooth Reference' => $toothInfo,
                'Treatment Date' => $dateFormatted,
                'Attending Dentist' => $dentistName,
                'Treatment Status' => ucfirst(str_replace('_', ' ', $treatment->status)),
            ]
        );

        self::send($user, $notification);
    }

    /**
     * Notify patient when a new invoice is created.
     */
    public static function notifyInvoiceCreated(Invoice $invoice): void
    {
        $user = $invoice->patient?->user;
        if (!$user) {
            return;
        }

        $totalFormatted = '₱' . number_format((float) ($invoice->net_amount ?? 0), 2);
        $balanceFormatted = '₱' . number_format((float) ($invoice->balance ?? 0), 2);
        $dateFormatted = Carbon::parse($invoice->invoice_date)->format('l, F j, Y');

        $notification = new PatientActivityNotification(
            title: "New Invoice #{$invoice->invoice_number}",
            message: "A new billing statement #{$invoice->invoice_number} of {$totalFormatted} has been issued for your clinic visit.",
            category: "billing",
            actionUrl: "/billing/{$invoice->id}",
            actionText: "View Invoice Statement",
            details: [
                'Invoice Number' => $invoice->invoice_number,
                'Billing Date' => $dateFormatted,
                'Total Amount' => $totalFormatted,
                'Balance Due' => $balanceFormatted,
                'Payment Status' => ucfirst(str_replace('_', ' ', $invoice->status)),
            ]
        );

        self::send($user, $notification);
    }

    /**
     * Notify patient when an official payment receipt is recorded.
     */
    public static function notifyPaymentReceived(Payment $payment): void
    {
        $user = $payment->patient?->user ?? $payment->invoice?->patient?->user;
        if (!$user) {
            return;
        }

        $amountFormatted = '₱' . number_format((float) ($payment->amount ?? 0), 2);
        $remainingBalance = $payment->invoice ? '₱' . number_format((float) ($payment->invoice->balance ?? 0), 2) : '₱0.00';
        $paymentDateFormatted = Carbon::parse($payment->payment_date)->format('l, F j, Y');

        $notification = new PatientActivityNotification(
            title: "Payment Received (Receipt #{$payment->payment_number})",
            message: "We have received your payment of {$amountFormatted} for Invoice #{$payment->invoice?->invoice_number}. Thank you!",
            category: "payment",
            actionUrl: "/payments",
            actionText: "View Official Receipt",
            details: [
                'Receipt Number' => $payment->payment_number,
                'Invoice Reference' => $payment->invoice?->invoice_number,
                'Amount Paid' => $amountFormatted,
                'Payment Date' => $paymentDateFormatted,
                'Payment Method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                'Remaining Balance' => $remainingBalance,
            ]
        );

        self::send($user, $notification);
    }

    /**
     * Notify clinic staff, secretaries, and dentist of a new appointment request from a patient.
     */
    public static function notifyStaffNewAppointment(Appointment $appointment): void
    {
        $patientName = $appointment->patient?->full_name 
            ?? $appointment->user?->name 
            ?? 'A patient';

        $dateFormatted = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $startFormatted = Carbon::parse($appointment->appointment_time)->format('g:i A');
        $endFormatted = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('g:i A') : null;
        $timeWindow = $endFormatted ? "{$startFormatted} – {$endFormatted}" : $startFormatted;

        $notification = new PatientActivityNotification(
            title: "New Appointment Request: {$patientName}",
            message: "{$patientName} has booked an appointment for {$appointment->service} on {$dateFormatted} at {$timeWindow}. Please review and confirm the schedule.",
            category: "appointment",
            actionUrl: "/appointments",
            actionText: "Review & Manage Appointments",
            details: [
                'Patient' => $patientName,
                'Procedure' => $appointment->service,
                'Requested Date' => $dateFormatted,
                'Time Slot' => $timeWindow,
                'Status' => 'Pending Clinic Approval',
            ]
        );

        // Notify clinic staff (secretary, staff, admin)
        $staffUsers = User::whereIn('role', ['admin', 'staff', 'secretary'])->get();
        foreach ($staffUsers as $staff) {
            self::send($staff, $notification);
        }

        // Notify assigned dentist if not already included
        if ($appointment->dentist && !$staffUsers->contains('id', $appointment->dentist->id)) {
            self::send($appointment->dentist, $notification);
        }
    }

    /**
     * Notify assigned dentist that an appointment has been confirmed on their schedule.
     */
    public static function notifyDentistAppointmentConfirmed(Appointment $appointment): void
    {
        $dentist = $appointment->dentist;
        if (!$dentist) {
            return;
        }

        $patientName = $appointment->patient?->full_name 
            ?? $appointment->user?->name 
            ?? 'Patient';

        $dateFormatted = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $startFormatted = Carbon::parse($appointment->appointment_time)->format('g:i A');
        $endFormatted = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('g:i A') : null;
        $timeWindow = $endFormatted ? "{$startFormatted} – {$endFormatted}" : $startFormatted;

        $notification = new PatientActivityNotification(
            title: "Appointment Confirmed on Your Schedule",
            message: "An appointment with {$patientName} for {$appointment->service} has been confirmed for {$dateFormatted} at {$timeWindow}.",
            category: "appointment",
            actionUrl: "/appointments",
            actionText: "View Schedule Desk",
            details: [
                'Patient' => $patientName,
                'Procedure' => $appointment->service,
                'Date' => $dateFormatted,
                'Time Window' => $timeWindow,
                'Duration' => ($appointment->duration_minutes ?: 60) . ' mins',
                'Status' => 'Confirmed',
            ]
        );

        self::send($dentist, $notification);
    }

    /**
     * Notify clinic staff (admin, staff, secretary) and assigned dentist about an upcoming appointment.
     */
    public static function notifyUpcomingAppointmentStaff(Appointment $appointment, string $timeframeDescription, string $reminderKey): int
    {
        $patientName = $appointment->patient?->full_name 
            ?? $appointment->user?->name 
            ?? 'Patient';

        $dateFormatted = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $startFormatted = Carbon::parse($appointment->appointment_time)->format('g:i A');
        $endFormatted = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('g:i A') : null;
        $timeWindow = $endFormatted ? "{$startFormatted} – {$endFormatted}" : $startFormatted;
        $dentistName = $appointment->dentist?->name ?? 'Dr. Valdez';

        $notification = new PatientActivityNotification(
            title: "Upcoming Appointment: {$patientName} ({$timeframeDescription})",
            message: "Reminder: {$patientName} is scheduled for {$appointment->service} {$timeframeDescription} at {$timeWindow} with {$dentistName}. Please ensure operatory readiness.",
            category: "appointment",
            actionUrl: "/appointments",
            actionText: "View Appointments Hub",
            details: [
                'Appointment ID' => (string) $appointment->id,
                'Reminder Key' => $reminderKey,
                'Patient' => $patientName,
                'Procedure' => $appointment->service,
                'Date' => $dateFormatted,
                'Time Slot' => $timeWindow,
                'Attending Dentist' => $dentistName,
                'Timeframe' => $timeframeDescription,
                'Status' => 'Confirmed',
            ]
        );

        $sentCount = 0;

        // 1. Notify clinic administrative staff (admin, secretary, staff)
        $staffUsers = User::whereIn('role', ['admin', 'staff', 'secretary'])->get();
        foreach ($staffUsers as $staff) {
            $alreadyNotified = $staff->notifications()
                ->where('data->details->Appointment ID', (string) $appointment->id)
                ->where('data->details->Reminder Key', $reminderKey)
                ->exists();

            if (!$alreadyNotified) {
                self::send($staff, $notification);
                $sentCount++;
            }
        }

        // 2. Notify assigned dentist
        if ($appointment->dentist && !$staffUsers->contains('id', $appointment->dentist->id)) {
            $alreadyNotified = $appointment->dentist->notifications()
                ->where('data->details->Appointment ID', (string) $appointment->id)
                ->where('data->details->Reminder Key', $reminderKey)
                ->exists();

            if (!$alreadyNotified) {
                self::send($appointment->dentist, $notification);
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Notify patient about their approaching appointment.
     */
    public static function notifyUpcomingAppointmentPatient(Appointment $appointment, string $timeframeDescription, string $reminderKey): bool
    {
        $user = $appointment->user ?? $appointment->patient?->user;
        if (!$user) {
            return false;
        }

        $alreadyNotified = $user->notifications()
            ->where('data->details->Appointment ID', (string) $appointment->id)
            ->where('data->details->Reminder Key', $reminderKey)
            ->exists();

        if ($alreadyNotified) {
            return false;
        }

        $appointment->loadMissing('patient.medicalHistory');
        $hasConditions = $appointment->patient?->medicalHistory?->hasConditions() ?? false;
        $clearancePresented = $appointment->patient?->medicalHistory?->medical_clearance_presented ?? false;

        $dateFormatted = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $startFormatted = Carbon::parse($appointment->appointment_time)->format('g:i A');
        $endFormatted = $appointment->end_time ? Carbon::parse($appointment->end_time)->format('g:i A') : null;
        $timeWindow = $endFormatted ? "{$startFormatted} – {$endFormatted}" : $startFormatted;
        $dentistName = $appointment->dentist?->name ?? 'Dr. Valdez';

        $message = "Friendly reminder from Vitality Smile Dental Clinic: Your appointment for {$appointment->service} is scheduled {$timeframeDescription} at {$timeWindow}. Please arrive 10 minutes prior to your slot.";
        $details = [
            'Appointment ID' => (string) $appointment->id,
            'Reminder Key' => $reminderKey,
            'Procedure' => $appointment->service,
            'Date' => $dateFormatted,
            'Time Window' => $timeWindow,
            'Attending Dentist' => $dentistName,
            'Timeframe' => $timeframeDescription,
            'Status' => 'Confirmed',
        ];

        if ($hasConditions && !$clearancePresented) {
            $message .= " ⚠️ Clearance Reminder: Our records indicate you have documented medical conditions. Please remember to bring your signed Physician Medical Clearance with you so your dentist can proceed with treatment.";
            $details['Medical Clearance'] = 'MANDATORY: Bring signed physician medical clearance letter.';
        }

        $notification = new PatientActivityNotification(
            title: "Reminder: Dental Appointment {$timeframeDescription}",
            message: $message,
            category: "appointment",
            actionUrl: "/appointments",
            actionText: "View Confirmed Appointment",
            details: $details
        );

        self::send($user, $notification);
        return true;
    }

    /**
     * Proactively notify a patient with documented medical history
     * that a Physician Medical Clearance is mandatory prior to receiving dental treatment.
     */
    public static function notifyMedicalClearanceRequired(\App\Models\Patient $patient, ?Appointment $appointment = null): void
    {
        $user = $patient->user;
        if (!$user) {
            return;
        }

        $patient->loadMissing('medicalHistory');
        $mh = $patient->medicalHistory;
        if (!$mh || !$mh->hasConditions() || $mh->medical_clearance_presented) {
            return;
        }

        // Avoid duplicate spam within the last 24 hours
        $recentNotice = $user->notifications()
            ->where('data->category', 'medical')
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($recentNotice) {
            return;
        }

        $conditionsList = [];
        if ($mh->hypertension) $conditionsList[] = 'Hypertension';
        if ($mh->diabetes) $conditionsList[] = 'Diabetes';
        if ($mh->heart_disease) $conditionsList[] = 'Heart Disease';
        if ($mh->bleeding_disorders) $conditionsList[] = 'Bleeding Disorders';
        if ($mh->asthma) $conditionsList[] = 'Asthma';
        if ($mh->kidney_disease) $conditionsList[] = 'Kidney Disease';
        if ($mh->liver_disease) $conditionsList[] = 'Liver Disease';
        if ($mh->hepatitis) $conditionsList[] = 'Hepatitis';
        if ($mh->epilepsy) $conditionsList[] = 'Epilepsy';
        if ($mh->is_pregnant) $conditionsList[] = 'Pregnancy';
        if ($mh->has_allergies || !empty($mh->allergies)) $conditionsList[] = 'Drug Allergies';

        $conditionsStr = !empty($conditionsList) ? implode(', ', $conditionsList) : 'Documented Health Conditions';

        $details = [
            'Requirement' => 'Physician Medical Clearance (Clearance for Dental Care)',
            'Flagged Conditions' => $conditionsStr,
            'Clinical Protocol' => 'Dentist cannot administer treatments without signed physician clearance.',
            'Action Needed' => 'Obtain written clearance from your physician and present it to clinic reception.',
        ];

        if ($appointment) {
            $details['Upcoming Appointment'] = Carbon::parse($appointment->appointment_date)->format('M d, Y') . " ({$appointment->service})";
        }

        $notification = new PatientActivityNotification(
            title: "Action Required: Physician Medical Clearance Mandatory",
            message: "Our clinic records show that you have documented medical conditions ({$conditionsStr}). In accordance with Vitality Smile clinical safety protocols, your dentist cannot perform dental procedures without an official Physician Medical Clearance signed by your attending doctor. Please secure your clearance letter and present it during your visit.",
            category: "medical",
            actionUrl: "/my-medical-history",
            actionText: "View Medical Requirements & History",
            details: $details
        );

        self::send($user, $notification);
    }

    /**
     * Master dispatcher for upcoming appointment reminders.
     */
    public static function dispatchUpcomingReminders(?string $window = null): array
    {
        $now = Carbon::now();
        $todayStr = $now->toDateString();
        $tomorrowStr = $now->copy()->addDay()->toDateString();

        $stats = [
            'staff_reminders_sent' => 0,
            'patient_reminders_sent' => 0,
            'appointments_processed' => 0,
        ];

        // 1. Process 24-Hour (Tomorrow's) confirmed appointments
        if ($window === null || $window === '24h' || $window === 'tomorrow') {
            $tomorrowAppointments = Appointment::with(['patient.user', 'user', 'dentist'])
                ->where('status', 'confirmed')
                ->where('appointment_date', $tomorrowStr)
                ->get();

            foreach ($tomorrowAppointments as $apt) {
                $timeframe = "tomorrow (" . Carbon::parse($apt->appointment_date)->format('M d') . ")";
                $key = "reminder_24h";

                $staffCount = self::notifyUpcomingAppointmentStaff($apt, $timeframe, $key);
                $patientSent = self::notifyUpcomingAppointmentPatient($apt, $timeframe, $key);

                $stats['staff_reminders_sent'] += $staffCount;
                if ($patientSent) {
                    $stats['patient_reminders_sent']++;
                }
                $stats['appointments_processed']++;
            }
        }

        // 2. Process Same-Day / Near Upcoming confirmed appointments (Today)
        if ($window === null || $window === 'today' || $window === 'near') {
            $todayAppointments = Appointment::with(['patient.user', 'user', 'dentist'])
                ->where('status', 'confirmed')
                ->where('appointment_date', $todayStr)
                ->get();

            foreach ($todayAppointments as $apt) {
                if (empty($apt->appointment_time)) {
                    continue;
                }

                $aptDateTime = Carbon::parse($apt->appointment_date . ' ' . $apt->appointment_time);
                $diffInMinutes = $now->diffInMinutes($aptDateTime, false);

                // Near reminder: within next 2 hours (1 to 120 minutes away)
                if ($diffInMinutes > 0 && $diffInMinutes <= 120) {
                    $timeframe = "starting in ~" . max(1, (int) round($diffInMinutes)) . " mins";
                    $key = "reminder_near_2h";

                    $staffCount = self::notifyUpcomingAppointmentStaff($apt, $timeframe, $key);
                    $patientSent = self::notifyUpcomingAppointmentPatient($apt, $timeframe, $key);

                    $stats['staff_reminders_sent'] += $staffCount;
                    if ($patientSent) {
                        $stats['patient_reminders_sent']++;
                    }
                    $stats['appointments_processed']++;
                } 
                // Morning / day-of reminder (if appointment is later today)
                elseif ($aptDateTime->isFuture()) {
                    $timeframe = "today at " . Carbon::parse($apt->appointment_time)->format('g:i A');
                    $key = "reminder_day_of";

                    $staffCount = self::notifyUpcomingAppointmentStaff($apt, $timeframe, $key);
                    $patientSent = self::notifyUpcomingAppointmentPatient($apt, $timeframe, $key);

                    $stats['staff_reminders_sent'] += $staffCount;
                    if ($patientSent) {
                        $stats['patient_reminders_sent']++;
                    }
                    $stats['appointments_processed']++;
                }
            }
        }

        return $stats;
    }
}
