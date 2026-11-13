<?php

namespace Database\Seeders;

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
            'email' => 'vitalityadmin01@gmail.com',
        ], [
            'name' => 'Dr. Valdez',
            'role' => 'admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // 2. Staff Account
        User::firstOrCreate([
            'email' => 'vitalitystaff01@gmail.com',
        ], [
            'name' => 'Staff Member',
            'role' => 'staff',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // 3. Secretary Account
        User::firstOrCreate([
            'email' => 'vitalitysecretary01@gmail.com',
        ], [
            'name' => 'Clinic Secretary',
            'role' => 'secretary',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
    }
}
