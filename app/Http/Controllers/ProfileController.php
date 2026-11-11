<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $patient = null;
        $medicalHistory = null;
        $stats = [
            'appointments_count' => 0,
            'treatments_count' => 0,
            'critical_alerts_count' => 0,
            'outstanding_balance' => 0.0,
        ];
        $nextAppointment = null;

        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first() ?? $user->ensurePatientProfile();

            if ($patient) {
                $patient->load('medicalHistory');
                $medicalHistory = $patient->medicalHistory;

                $stats['appointments_count'] = Appointment::where('user_id', $user->id)
                    ->orWhere('patient_id', $patient->id)
                    ->count();

                $stats['treatments_count'] = Treatment::where('patient_id', $patient->id)
                    ->where('status', 'completed')
                    ->count();

                $stats['critical_alerts_count'] = count($medicalHistory?->critical_alerts ?? []);

                $stats['outstanding_balance'] = (float) Invoice::where('patient_id', $patient->id)
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->sum('balance');

                $nextAppointment = Appointment::where(function ($q) use ($user, $patient) {
                        $q->where('user_id', $user->id)
                            ->orWhere('patient_id', $patient->id);
                    })
                    ->where('appointment_date', '>=', now()->toDateString())
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->orderBy('appointment_date', 'asc')
                    ->orderBy('appointment_time', 'asc')
                    ->with('dentist')
                    ->first();
            }
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'patient' => $patient,
            'medicalHistory' => $medicalHistory,
            'stats' => $stats,
            'nextAppointment' => $nextAppointment,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first() ?? $user->ensurePatientProfile();

            $firstName = trim($request->input('first_name', ''));
            $middleName = trim($request->input('middle_name', ''));
            $lastName = trim($request->input('last_name', ''));
            $suffix = trim($request->input('suffix', ''));

            // Build full name for user account
            if ($firstName || $lastName) {
                $fullNameParts = array_filter([$firstName, $middleName, $lastName]);
                $fullName = implode(' ', $fullNameParts);
                if ($suffix) {
                    $fullName .= " {$suffix}";
                }
                $user->name = $fullName;
            } elseif ($request->filled('name')) {
                $user->name = $request->input('name');
            }

            if ($patient) {
                $patient->update([
                    'first_name' => $firstName ?: ($patient->first_name ?: $user->name),
                    'middle_name' => $middleName ?: null,
                    'last_name' => $lastName ?: ($patient->last_name ?: ''),
                    'suffix' => $suffix ?: null,
                    'date_of_birth' => $request->input('date_of_birth', $patient->date_of_birth),
                    'gender' => $request->input('gender', $patient->gender ?? 'Other'),
                    'contact_number' => $request->input('contact_number', $patient->contact_number),
                    'address' => $request->input('address', $patient->address),
                    'guardian' => $request->input('guardian', $patient->guardian),
                    'email' => $request->input('email', $user->email),
                ]);
            }
        } else {
            $user->name = $request->input('name', $user->name);
        }

        $user->email = $request->input('email');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
