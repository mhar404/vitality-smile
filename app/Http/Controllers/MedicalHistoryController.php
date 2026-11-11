<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMedicalHistoryRequest;
use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MedicalHistoryController extends Controller
{
    /**
     * Show form for editing or adding patient's medical history.
     */
    public function edit(Patient $patient)
    {
        $user = Auth::user();

        // Check permission
        if ($user->role === 'patient' && $patient->user_id !== $user->id) {
            abort(403, 'Unauthorized access to medical history.');
        }

        $medicalHistory = $patient->medicalHistory;

        return Inertia::render('Patients/MedicalHistory/Edit', [
            'patient' => $patient,
            'medicalHistory' => $medicalHistory,
        ]);
    }

    /**
     * Store or update patient's medical history.
     */
    public function storeOrUpdate(SaveMedicalHistoryRequest $request, Patient $patient)
    {
        $user = Auth::user();

        if ($user->role === 'patient' && $patient->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $mh = $patient->medicalHistory()->updateOrCreate(
            ['patient_id' => $patient->id],
            $request->validated()
        );

        // If patient has recorded conditions without verified clearance, notify them of clearance requirement
        if ($mh && $mh->hasConditions() && !$mh->medical_clearance_presented) {
            \App\Services\PatientNotificationService::notifyMedicalClearanceRequired($patient);
        }

        if ($user->role === 'patient') {
            $msg = $mh && $mh->hasConditions() && !$mh->medical_clearance_presented
                ? 'Your medical history has been updated. Please note: Due to your recorded medical conditions, a Physician Medical Clearance is required prior to dental treatment.'
                : 'Your medical history has been updated.';
            return redirect()->route('dashboard')->with('success', $msg);
        }

        return redirect()->route('patients.show', $patient->id)
            ->with('success', 'Medical history updated successfully.');
    }

    /**
     * Patient view of their own medical history.
     */
    public function myHistory(Request $request)
    {
        $user = Auth::user();
        $patient = Patient::where('user_id', $user->id)->first() ?? ($user->hasVerifiedEmail() ? $user->ensurePatientProfile() : null);

        if (!$patient) {
            return redirect()->route('dashboard')->with('error', 'Patient profile not found.');
        }

        $medicalHistory = $patient->medicalHistory;

        return Inertia::render('Patients/MedicalHistory/MyHistory', [
            'patient' => $patient,
            'medicalHistory' => $medicalHistory,
        ]);
    }
}
