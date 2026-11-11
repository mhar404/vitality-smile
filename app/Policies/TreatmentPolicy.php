<?php

namespace App\Policies;

use App\Models\Treatment;
use App\Models\User;

class TreatmentPolicy
{
    /**
     * Determine whether the user can view any treatments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the treatment.
     */
    public function view(User $user, Treatment $treatment): bool
    {
        if (in_array($user->role, ['admin', 'secretary', 'dentist', 'staff'])) {
            return true;
        }

        // Patient can view if this treatment belongs to their patient record
        if ($user->role === 'patient') {
            return $treatment->patient && $treatment->patient->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create treatments.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'dentist']);
    }

    /**
     * Determine whether the user can update the treatment.
     */
    public function update(User $user, Treatment $treatment): bool
    {
        return in_array($user->role, ['admin', 'dentist']);
    }

    /**
     * Determine whether the user can delete the treatment.
     */
    public function delete(User $user, Treatment $treatment): bool
    {
        return in_array($user->role, ['admin', 'dentist']);
    }
}
