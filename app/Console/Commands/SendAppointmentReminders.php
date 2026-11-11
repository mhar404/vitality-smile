<?php

namespace App\Console\Commands;

use App\Services\PatientNotificationService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders {--window= : Filter by reminder window (24h, today, near)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan upcoming confirmed appointments and dispatch reminder notifications to staff, dentists, secretaries, admins, and patients.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $window = $this->option('window');
        $this->info("Scanning confirmed appointments for upcoming reminders" . ($window ? " [Window: {$window}]" : "") . "...");

        $stats = PatientNotificationService::dispatchUpcomingReminders($window);

        $this->info("Completed reminder dispatch:");
        $this->line("- Confirmed appointments processed: {$stats['appointments_processed']}");
        $this->line("- Staff / Dentist notifications sent: {$stats['staff_reminders_sent']}");
        $this->line("- Patient notifications sent: {$stats['patient_reminders_sent']}");

        return Command::SUCCESS;
    }
}

