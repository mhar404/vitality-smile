<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;
    public function test_non_admin_cannot_access_backup(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($patient)->get(route('backup.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_backup_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('backup.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Backup/Index')
            ->has('stats')
            ->has('backups')
        );
    }

    public function test_admin_can_create_backup_snapshot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('backup.store'), [
            'type' => 'database',
            'note' => 'Feature test snapshot',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}

