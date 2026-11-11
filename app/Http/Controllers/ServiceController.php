<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Treatment;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ServiceController extends Controller
{
    /**
     * Standard categories for dental clinic services.
     */
    protected array $defaultCategories = [
        ['id' => 'all', 'name' => 'All Services'],
        ['id' => 'general', 'name' => 'General & Preventive'],
        ['id' => 'restorative', 'name' => 'Restorative & Endodontics'],
        ['id' => 'cosmetic', 'name' => 'Cosmetic & Crowns'],
        ['id' => 'surgery', 'name' => 'Oral Surgery'],
        ['id' => 'prosthodontics', 'name' => 'Dentures & Prosthetics'],
        ['id' => 'orthodontics', 'name' => 'Orthodontics & Braces'],
    ];

    /**
     * Display a listing of services (Dual: Public catalog or Staff Management Console).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isStaff = $user && in_array($user->role, ['admin', 'staff', 'secretary', 'dentist']);

        // 1. Staff / Admin Management Console
        if ($isStaff && $request->query('view') !== 'public') {
            $query = Service::query();

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Category filter
            if ($request->filled('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            // Status filter
            if ($request->filled('status') && $request->status !== 'all') {
                $isActive = $request->status === 'active';
                $query->where('is_active', $isActive);
            }

            $services = $query->ordered()->paginate(12)->withQueryString();

            $metrics = [
                'total_services' => Service::count(),
                'active_services' => Service::where('is_active', true)->count(),
                'inactive_services' => Service::where('is_active', false)->count(),
                'categories_count' => Service::distinct('category')->count('category'),
                'avg_base_price' => (float) Service::where('is_active', true)->avg('base_price') ?? 0,
            ];

            return Inertia::render('Services/Manage', [
                'services' => $services,
                'filters' => $request->only(['search', 'category', 'status']),
                'metrics' => $metrics,
                'categories' => $this->defaultCategories,
            ]);
        }

        // 2. Public / Patient View
        $services = Service::active()->ordered()->get();

        return Inertia::render('Services', [
            'services' => $services,
            'categories' => $this->defaultCategories,
            'isStaff' => $isStaff,
        ]);
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(StoreServiceRequest $request)
    {
        if (Auth::user()?->role === 'staff') {
            abort(403, 'Staff accounts have view-only access to services.');
        }

        $validated = $request->validated();

        // Clean up empty highlights if any
        if (isset($validated['highlights']) && is_array($validated['highlights'])) {
            $validated['highlights'] = array_values(array_filter(array_map('trim', $validated['highlights'])));
        }

        $service = Service::create($validated);

        AuditLogger::log(
            module: 'services',
            action: 'created',
            description: "Created dental service '{$service->title}' ({$service->category}) with base fee ₱" . number_format($service->base_price, 2) . ".",
            model: $service,
            newValues: $service->only(['title', 'category', 'base_price', 'duration_minutes', 'is_active'])
        );

        return redirect()->route('services.index')
            ->with('success', 'Dental service created successfully.');
    }

    /**
     * Update the specified service in storage.
     */
    public function update(UpdateServiceRequest $request, Service $service)
    {
        if (Auth::user()?->role === 'staff') {
            abort(403, 'Staff accounts have view-only access to services.');
        }

        $validated = $request->validated();

        if (isset($validated['highlights']) && is_array($validated['highlights'])) {
            $validated['highlights'] = array_values(array_filter(array_map('trim', $validated['highlights'])));
        }

        $oldValues = $service->only(['title', 'category', 'base_price', 'duration_minutes', 'is_active']);
        $service->update($validated);

        AuditLogger::log(
            module: 'services',
            action: 'updated',
            description: "Updated dental service '{$service->title}'.",
            model: $service,
            oldValues: $oldValues,
            newValues: $service->only(['title', 'category', 'base_price', 'duration_minutes', 'is_active'])
        );

        return redirect()->route('services.index')
            ->with('success', 'Dental service updated successfully.');
    }

    /**
     * Toggle the active status of the service.
     */
    public function toggleStatus(Request $request, Service $service)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'secretary', 'dentist'])) {
            abort(403, 'Unauthorized.');
        }

        $wasActive = $service->is_active;
        $service->update([
            'is_active' => !$service->is_active,
        ]);

        $statusLabel = $service->is_active ? 'activated' : 'deactivated';

        AuditLogger::log(
            module: 'services',
            action: 'status_changed',
            description: "Changed status of dental service '{$service->title}' to {$statusLabel}.",
            model: $service,
            oldValues: ['is_active' => $wasActive],
            newValues: ['is_active' => $service->is_active]
        );

        return redirect()->back()
            ->with('success', "Service '{$service->title}' has been {$statusLabel}.");
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Request $request, Service $service)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'secretary', 'dentist'])) {
            abort(403, 'Unauthorized action.');
        }

        // Check if service is referenced in appointments or treatments
        $referencedInAppointments = Appointment::where('service', 'like', "%{$service->name}%")
            ->orWhere('service', 'like', "%{$service->title}%")
            ->exists();

        $referencedInTreatments = Treatment::where('procedure_name', 'like', "%{$service->title}%")
            ->orWhere('procedure_name', 'like', "%{$service->name}%")
            ->exists();

        if ($referencedInAppointments || $referencedInTreatments) {
            $service->update(['is_active' => false]);
            return redirect()->route('services.index')
                ->with('info', "Service '{$service->title}' is referenced in clinical records and has been deactivated instead of deleted.");
        }

        $service->delete();

        return redirect()->route('services.index')
            ->with('success', "Service '{$service->title}' deleted successfully.");
    }
}
