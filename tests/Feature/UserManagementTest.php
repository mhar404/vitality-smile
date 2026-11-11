<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;
    public function test_non_admin_cannot_access_user_management(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($patient)->get(route('users.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_users_directory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Users/Index')
            ->has('users')
            ->has('metrics')
            ->has('roleDefinitions')
        );
    }

    public function test_admin_can_create_new_staff_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Receptionist Clara',
            'email' => 'clara@vitalitysmile.com',
            'phone_number' => '09123456789',
            'role' => 'staff',
            'password' => 'SecurePass123!',
            'email_verified' => true,
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'clara@vitalitysmile.com',
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);

        $response = $this->actingAs($admin)->patch(route('users.toggle-status', $staff->id));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->patch(route('users.toggle-status', $admin->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }
}

