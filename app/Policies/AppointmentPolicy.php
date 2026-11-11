<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any appointments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if (in_array($user->role, ['admin', 'secretary', 'dentist', 'staff'])) {
            return true;
        }

        return $user->id === $appointment->user_id;
    }

    /**
     * Determine whether the user can create appointments.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'secretary', 'dentist', 'patient']);
    }

    /**
     * Determine whether the user can update the appointment.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        if (in_array($user->role, ['admin', 'secretary', 'dentist'])) {
            return true;
        }

        // Patient can update/cancel their own pending appointment
        return $user->id === $appointment->user_id && $appointment->status === 'pending';
    }

    /**
     * Determine whether the user can delete/cancel the appointment.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        if (in_array($user->role, ['admin', 'secretary', 'dentist'])) {
            return true;
        }

        return $user->id === $appointment->user_id;
    }
}
