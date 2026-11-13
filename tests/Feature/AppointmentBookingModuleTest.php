<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $dentist;
    protected User $patientUser;
    protected Patient $patient;
    protected User $secretary;
    protected Service $cleaningService;
    protected Service $rootCanalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentist = User::factory()->create([
            'name' => 'Dr. Valdez',
            'role' => 'dentist',
            'email' => 'vitalityadmin01@gmail.com',
        ]);

        $this->secretary = User::factory()->create([
            'name' => 'Secretary Anna',
            'role' => 'secretary',
            'email' => 'vitalitysecretary01@gmail.com',
        ]);

        $this->patientUser = User::factory()->create([
            'name' => 'Carlos Patient',
            'role' => 'patient',
            'email' => 'carlos@example.com',
        ]);

        $this->patient = Patient::create([
            'user_id' => $this->patientUser->id,
            'patient_id' => 'PAT-2026-9999',
            'first_name' => 'Carlos',
            'last_name' => 'Patient',
            'email' => $this->patientUser->email,
            'status' => 'Active',
            'gender' => 'Male',
        ]);

        $this->cleaningService = Service::create([
            'name' => 'Oral Prophylaxis (Dental Cleaning)',
            'title' => 'Oral Prophylaxis (Dental Cleaning)',
            'category' => 'general',
            'base_price' => 800,
            'duration' => '60 mins',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $this->rootCanalService = Service::create([
            'name' => 'Root Canal Treatment (₱8,000 per canal)',
            'title' => 'Root Canal Treatment (Endodontic Therapy)',
            'category' => 'restorative',
            'base_price' => 8000,
            'duration' => '90 mins',
            'duration_minutes' => 90,
            'is_active' => true,
        ]);
    }

    protected function nextWeekday(): string
    {
        $date = Carbon::now()->addDays(2);
        while ($date->isWeekend()) {
            $date->addDay();
        }
        return $date->toDateString();
    }

    public function test_availability_endpoint_returns_slots_with_estimated_end_times(): void
    {
        $date = $this->nextWeekday();

        $response = $this->actingAs($this->patientUser)
            ->getJson(route('appointments.availability', [
                'date' => $date,
                'duration_minutes' => 60,
                'service' => $this->cleaningService->name,
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'date',
            'is_operating_day',
            'clinic_hours',
            'duration_minutes',
            'slots' => [
                '*' => ['time', 'end_time', 'label', 'end_label', 'available', 'reason'],
            ],
        ]);

        $data = $response->json();
        $this->assertTrue($data['is_operating_day']);
        $this->assertEquals(60, $data['duration_minutes']);

        // 9:00 AM slot ends at 10:00 AM
        $firstSlot = $data['slots'][0];
        $this->assertEquals('09:00', $firstSlot['time']);
        $this->assertEquals('10:00', $firstSlot['end_time']);
    }

    public function test_confirmed_appointment_blocks_overlapping_time_slots(): void
    {
        $date = $this->nextWeekday();

        // Confirmed appointment from 09:00 to 10:00
        Appointment::create([
            'user_id' => $this->patientUser->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service' => $this->cleaningService->name,
            'duration_minutes' => 60,
            'appointment_date' => $date,
            'appointment_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->patientUser)
            ->getJson(route('appointments.availability', [
                'date' => $date,
                'duration_minutes' => 60,
            ]));

        $response->assertOk();
        $slots = collect($response->json('slots'));

        // 09:00 slot (09:00-10:00) must be unavailable
        $slot900 = $slots->firstWhere('time', '09:00');
        $this->assertFalse($slot900['available']);

        // 09:30 slot (09:30-10:30, overlaps 09:00-10:00) must be unavailable
        $slot930 = $slots->firstWhere('time', '09:30');
        $this->assertFalse($slot930['available']);

        // 10:00 slot (10:00-11:00, starts exactly when previous ends) must be available
        $slot1000 = $slots->firstWhere('time', '10:00');
        $this->assertTrue($slot1000['available']);
    }

    public function test_backend_prevents_booking_overlapping_with_confirmed_appointment(): void
    {
        $date = $this->nextWeekday();

        // Existing confirmed appointment: 10:00 to 11:00
        Appointment::create([
            'user_id' => $this->patientUser->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service' => $this->cleaningService->name,
            'duration_minutes' => 60,
            'appointment_date' => $date,
            'appointment_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
        ]);

        // Attempting to book 10:30 to 11:30 must be blocked
        $response = $this->actingAs($this->patientUser)
            ->post(route('appointments.store'), [
                'service' => $this->cleaningService->name,
                'appointment_date' => $date,
                'appointment_time' => '10:30',
            ]);

        $response->assertSessionHasErrors(['appointment_time']);
    }

    public function test_booking_that_exceeds_closing_time_is_rejected(): void
    {
        $date = $this->nextWeekday();

        // 90-minute root canal at 17:30 would finish at 19:00 (past 18:00 clinic close)
        $response = $this->actingAs($this->patientUser)
            ->post(route('appointments.store'), [
                'service' => $this->rootCanalService->name,
                'appointment_date' => $date,
                'appointment_time' => '17:30',
            ]);

        $response->assertSessionHasErrors(['appointment_time']);
    }

    public function test_secretary_approval_rechecks_availability_and_prevents_conflicts(): void
    {
        $date = $this->nextWeekday();

        // Already confirmed appointment from 14:00 to 15:00
        Appointment::create([
            'user_id' => $this->patientUser->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service' => $this->cleaningService->name,
            'duration_minutes' => 60,
            'appointment_date' => $date,
            'appointment_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
        ]);

        // Another pending appointment request for 14:30
        $pendingApt = Appointment::create([
            'user_id' => $this->patientUser->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service' => $this->cleaningService->name,
            'duration_minutes' => 60,
            'appointment_date' => $date,
            'appointment_time' => '14:30:00',
            'end_time' => '15:30:00',
            'status' => 'pending',
        ]);

        // Secretary attempts to confirm the conflicting pending appointment
        $response = $this->actingAs($this->secretary)
            ->patch(route('appointments.status.update', $pendingApt->id), [
                'status' => 'confirmed',
            ]);

        // Must reject and flash error
        $response->assertSessionHas('error');
        $this->assertEquals('pending', $pendingApt->fresh()->status);
    }

    public function test_cancelling_appointment_immediately_frees_availability(): void
    {
        $date = $this->nextWeekday();

        $apt = Appointment::create([
            'user_id' => $this->patientUser->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'service' => $this->cleaningService->name,
            'duration_minutes' => 60,
            'appointment_date' => $date,
            'appointment_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'confirmed',
        ]);

        // Cancel it
        $this->actingAs($this->secretary)
            ->patch(route('appointments.status.update', $apt->id), [
                'status' => 'cancelled',
            ]);

        $this->assertEquals('cancelled', $apt->fresh()->status);

        // Check availability
        $response = $this->actingAs($this->patientUser)
            ->getJson(route('appointments.availability', [
                'date' => $date,
                'duration_minutes' => 60,
            ]));

        $slot1100 = collect($response->json('slots'))->firstWhere('time', '11:00');
        $this->assertTrue($slot1100['available']);
    }
}

