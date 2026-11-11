<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ServiceSeeder::class);
    }

    public function test_guest_can_view_public_services_page(): void
    {
        $response = $this->get(route('services.index'));
        $response->assertStatus(200);
    }

    public function test_staff_can_view_services_management_page(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('services.index'));
        $response->assertStatus(200);
    }

    public function test_staff_can_create_new_dental_service(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('services.store'), [
            'title' => 'Dental Sealant (Per Tooth)',
            'name' => 'Dental Sealant (₱1,200)',
            'category' => 'preventive',
            'base_price' => 1200.00,
            'max_price' => null,
            'price_display' => '₱1,200',
            'duration' => '20 - 30 mins',
            'icon' => '🛡️',
            'description' => 'Protective resin coating applied to the chewing surfaces of back teeth to prevent cavities.',
            'highlights' => ['Painless application', 'Long-lasting cavity protection'],
            'is_active' => true,
            'sort_order' => 15,
        ]);

        $response->assertRedirect(route('services.index'));
        $this->assertDatabaseHas('services', [
            'title' => 'Dental Sealant (Per Tooth)',
            'name' => 'Dental Sealant (₱1,200)',
        ]);
    }

    public function test_staff_can_toggle_service_status(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $service = Service::first();

        $initialStatus = $service->is_active;

        $response = $this->actingAs($staff)->patch(route('services.toggle-status', $service->id));
        $response->assertStatus(302);

        $this->assertEquals(!$initialStatus, $service->fresh()->is_active);
    }
}
