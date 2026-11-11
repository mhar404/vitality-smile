<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $dentist = User::where('role', 'dentist')->first() ?? $admin;
        $staff = User::where('role', 'staff')->first() ?? $admin;

        $ips = ['192.168.1.15', '192.168.1.42', '120.29.74.18', '112.198.88.2', '127.0.0.1'];
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
        ];

        // 1. Patient creation logs
        $patients = Patient::take(15)->get();
        foreach ($patients as $idx => $patient) {
            $createdTime = $patient->created_at ?? now()->subDays(15 - $idx);
            AuditLog::create([
                'user_id' => $staff?->id,
                'user_name' => $staff?->name ?? 'Staff Member',
                'user_role' => $staff?->role ?? 'staff',
                'module' => 'patients',
                'action' => 'created',
                'description' => "Registered new patient record for {$patient->first_name} {$patient->last_name} ({$patient->patient_id}).",
                'auditable_type' => Patient::class,
                'auditable_id' => $patient->id,
                'new_values' => [
                    'patient_id' => $patient->patient_id,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'contact_number' => $patient->contact_number,
                    'status' => $patient->status,
                ],
                'ip_address' => $ips[$idx % count($ips)],
                'user_agent' => $userAgents[$idx % count($userAgents)],
                'created_at' => $createdTime,
                'updated_at' => $createdTime,
            ]);
        }

        // 2. Appointment booking & confirmation logs
        $appointments = Appointment::with(['patient', 'user'])->take(20)->get();
        foreach ($appointments as $idx => $apt) {
            $createdTime = $apt->created_at ?? now()->subDays(10 - ($idx % 8));
            $actor = ($apt->user && $apt->user->role === 'patient') ? $apt->user : ($staff ?? $admin);
            
            // Booking log
            AuditLog::create([
                'user_id' => $actor->id,
                'user_name' => $actor->name,
                'user_role' => $actor->role,
                'module' => 'appointments',
                'action' => 'created',
                'description' => "Appointment #{$apt->id} booked for {$apt->service} on {$apt->appointment_date} at " . Carbon::parse($apt->appointment_time)->format('g:i A') . ".",
                'auditable_type' => Appointment::class,
                'auditable_id' => $apt->id,
                'new_values' => [
                    'service' => $apt->service,
                    'appointment_date' => $apt->appointment_date,
                    'appointment_time' => $apt->appointment_time,
                    'status' => 'pending',
                ],
                'ip_address' => $ips[($idx + 1) % count($ips)],
                'user_agent' => $userAgents[($idx + 1) % count($userAgents)],
                'created_at' => $createdTime,
                'updated_at' => $createdTime,
            ]);

            // If confirmed or completed, add status change log
            if (in_array($apt->status, ['confirmed', 'completed'])) {
                $confirmTime = Carbon::parse($createdTime)->addHours(2);
                AuditLog::create([
                    'user_id' => $staff?->id ?? $admin?->id,
                    'user_name' => $staff?->name ?? 'Staff Member',
                    'user_role' => 'staff',
                    'module' => 'appointments',
                    'action' => 'confirmed',
                    'description' => "Updated appointment #{$apt->id} ({$apt->service}) status from 'pending' to 'confirmed'.",
                    'auditable_type' => Appointment::class,
                    'auditable_id' => $apt->id,
                    'old_values' => ['status' => 'pending'],
                    'new_values' => ['status' => 'confirmed'],
                    'ip_address' => $ips[($idx + 2) % count($ips)],
                    'user_agent' => $userAgents[0],
                    'created_at' => $confirmTime,
                    'updated_at' => $confirmTime,
                ]);
            }
        }

        // 3. Treatment logs
        $treatments = Treatment::with('patient')->take(15)->get();
        foreach ($treatments as $idx => $t) {
            $createdTime = $t->created_at ?? now()->subDays(7 - ($idx % 5));
            AuditLog::create([
                'user_id' => $dentist?->id ?? $admin?->id,
                'user_name' => $dentist?->name ?? 'Dr. Valdez',
                'user_role' => 'dentist',
                'module' => 'treatments',
                'action' => 'created',
                'description' => "Documented treatment record #{$t->id} ({$t->procedure_name}) for patient ID #{$t->patient_id}.",
                'auditable_type' => Treatment::class,
                'auditable_id' => $t->id,
                'new_values' => [
                    'patient_id' => $t->patient_id,
                    'procedure_name' => $t->procedure_name,
                    'tooth_number' => $t->tooth_number,
                    'treatment_date' => $t->treatment_date,
                    'status' => $t->status,
                ],
                'ip_address' => $ips[($idx + 3) % count($ips)],
                'user_agent' => $userAgents[1],
                'created_at' => $createdTime,
                'updated_at' => $createdTime,
            ]);
        }

        // 4. Billing / Invoice logs
        $invoices = Invoice::with('patient')->take(15)->get();
        foreach ($invoices as $idx => $inv) {
            $createdTime = $inv->created_at ?? now()->subDays(6 - ($idx % 5));
            AuditLog::create([
                'user_id' => $staff?->id ?? $admin?->id,
                'user_name' => $staff?->name ?? 'Staff Member',
                'user_role' => 'staff',
                'module' => 'billing',
                'action' => 'created',
                'description' => "Generated Invoice #{$inv->invoice_number} for ₱" . number_format($inv->total_amount, 2) . " (Patient ID #{$inv->patient_id}).",
                'auditable_type' => Invoice::class,
                'auditable_id' => $inv->id,
                'new_values' => [
                    'invoice_number' => $inv->invoice_number,
                    'patient_id' => $inv->patient_id,
                    'total_amount' => $inv->total_amount,
                    'balance_due' => $inv->balance_due,
                    'status' => $inv->status,
                ],
                'ip_address' => $ips[($idx + 2) % count($ips)],
                'user_agent' => $userAgents[0],
                'created_at' => $createdTime,
                'updated_at' => $createdTime,
            ]);
        }

        // 5. Payment logs
        $payments = Payment::with('invoice')->take(15)->get();
        foreach ($payments as $idx => $p) {
            $createdTime = $p->created_at ?? now()->subDays(4 - ($idx % 4));
            AuditLog::create([
                'user_id' => $staff?->id ?? $admin?->id,
                'user_name' => $staff?->name ?? 'Staff Member',
                'user_role' => 'staff',
                'module' => 'payments',
                'action' => 'created',
                'description' => "Recorded payment #{$p->payment_number} of ₱" . number_format($p->amount, 2) . " via {$p->payment_method} for Invoice #" . ($p->invoice?->invoice_number ?? 'INV-GENERAL') . ".",
                'auditable_type' => Payment::class,
                'auditable_id' => $p->id,
                'new_values' => [
                    'payment_number' => $p->payment_number,
                    'amount' => $p->amount,
                    'payment_method' => $p->payment_method,
                    'reference_number' => $p->reference_number,
                ],
                'ip_address' => $ips[($idx + 4) % count($ips)],
                'user_agent' => $userAgents[0],
                'created_at' => $createdTime,
                'updated_at' => $createdTime,
            ]);
        }

        // 6. Services & System config logs
        $services = Service::take(5)->get();
        foreach ($services as $idx => $svc) {
            $createdTime = now()->subDays(12 - $idx);
            AuditLog::create([
                'user_id' => $admin?->id,
                'user_name' => $admin?->name ?? 'Admin User',
                'user_role' => 'admin',
                'module' => 'services',
                'action' => 'created',
                'description' => "Created dental service '{$svc->title}' ({$svc->category}) with base fee ₱" . number_format($svc->base_price, 2) . ".",
                'auditable_type' => Service::class,
                'auditable_id' => $svc->id,
                'new_values' => [
                    'title' => $svc->title,
                    'category' => $svc->category,
                    'base_price' => $svc->base_price,
                    'is_active' => $svc->is_active,
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => $userAgents[0],
                'created_at' => $createdTime,
                'updated_at' => $createdTime,
            ]);
        }
    }
}

