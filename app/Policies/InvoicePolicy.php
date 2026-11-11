<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if (in_array($user->role, ['admin', 'secretary', 'dentist', 'staff'])) {
            return true;
        }

        return $user->role === 'patient' && $invoice->patient && $invoice->patient->user_id === $user->id;
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'secretary', 'dentist']);
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['admin', 'secretary', 'dentist']);
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['admin', 'dentist']);
    }
}
