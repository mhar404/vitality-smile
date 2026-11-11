<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;
    public function test_non_admin_cannot_access_audit_logs(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($patient)->get(route('audit-logs.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_audit_logs_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Log a test event
        AuditLogger::log(
            module: 'appointments',
            action: 'created',
            description: 'Test appointment booked for audit test',
            actor: $admin
        );

        $response = $this->actingAs($admin)->get(route('audit-logs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AuditLog/Index')
            ->has('logs')
            ->has('metrics')
            ->has('availableModules')
            ->has('availableActions')
        );
    }

    public function test_admin_can_export_audit_logs_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AuditLogger::log(
            module: 'billing',
            action: 'created',
            description: 'Test invoice generated',
            actor: $admin
        );

        $response = $this->actingAs($admin)->get(route('audit-logs.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_admin_can_purge_old_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create an old audit log
        $oldLog = AuditLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'user_role' => 'admin',
            'module' => 'system',
            'action' => 'test',
            'description' => 'Very old test log',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        $this->assertDatabaseHas('audit_logs', ['id' => $oldLog->id]);

        $response = $this->actingAs($admin)->delete(route('audit-logs.purge'), [
            'days' => 90,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('audit_logs', ['id' => $oldLog->id]);
    }
}

