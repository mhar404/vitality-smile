<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed clinic dental services catalog
        $this->call(ServiceSeeder::class);

        // 1. Admin Account (Dr. Valdez - Clinic Administrator & Attending Dentist)
        User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Dr. Valdez',
            'role' => 'admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // 2. Staff Account
        User::firstOrCreate([
            'email' => 'staff@example.com',
        ], [
            'name' => 'Staff Member',
            'role' => 'staff',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // 3. Secretary Account
        User::firstOrCreate([
            'email' => 'secretary@example.com',
        ], [
            'name' => 'Clinic Secretary',
            'role' => 'secretary',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // 4. Patient User Account
        $patientUser = User::firstOrCreate([
            'email' => 'patient@example.com',
        ], [
            'name' => 'Jane Patient',
            'role' => 'patient',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // Clean Patient Profile (no transactions or appointment records)
        Patient::firstOrCreate([
            'email' => 'patient@example.com',
        ], [
            'user_id' => $patientUser->id,
            'patient_id' => 'PAT-' . date('Ymd') . '-001',
            'first_name' => 'Jane',
            'last_name' => 'Patient',
            'date_of_birth' => '1995-05-15',
            'gender' => 'Female',
            'contact_number' => '09123456789',
            'address' => '123 Vitality St., Manila',
            'status' => 'Active',
        ]);
    }
}
