<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Standard roles with display titles and permission summaries.
     */
    protected array $roleDefinitions = [
        'admin' => [
            'key' => 'admin',
            'name' => 'Clinic Administrator',
            'badge' => 'bg-purple-100 text-purple-800 border-purple-200',
            'description' => 'Full administrative privileges: user management, audit compliance, system reports, financial controls, and clinical oversight.',
            'permissions' => [
                'Manage all user accounts & credentials',
                'View & export system audit logs',
                'Access clinical analytics & executive reports',
                'Manage clinic dental services catalog',
                'Full billing, payments, and patient management',
            ],
        ],
        'dentist' => [
            'key' => 'dentist',
            'name' => 'Attending Dentist',
            'badge' => 'bg-teal-100 text-teal-800 border-teal-200',
            'description' => 'Clinical authority for dental diagnoses, patient charting, procedures, treatments, and medical histories.',
            'permissions' => [
                'View & manage scheduled appointments',
                'Document & update clinical treatments',
                'View & update patient medical histories & odontograms',
                'View patient diagnostic charts & dental history',
                'Manage clinical notes and procedures',
            ],
        ],
        'staff' => [
            'key' => 'staff',
            'name' => 'Clinic Staff / Reception',
            'badge' => 'bg-sky-100 text-sky-800 border-sky-200',
            'description' => 'Front-desk operations, patient registration, appointment scheduling, billing statements, and payments.',
            'permissions' => [
                'Register & manage patient profiles',
                'Book, approve, and reschedule appointments',
                'Generate billing invoices & official receipts',
                'Record and track cash/digital payments',
                'Dispatch SMS/Email notifications to patients',
            ],
        ],
        'secretary' => [
            'key' => 'secretary',
            'name' => 'Clinic Secretary',
            'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
            'description' => 'Appointment calendar coordination, patient triage, and front-desk schedule management.',
            'permissions' => [
                'Manage doctor appointment schedule',
                'Review incoming patient booking requests',
                'Manage patient contact logs',
                'Coordinate clinic operating hours and availability',
            ],
        ],
        'patient' => [
            'key' => 'patient',
            'name' => 'Registered Patient',
            'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'description' => 'Patient portal access for personal appointments, digital medical records, and billing receipts.',
            'permissions' => [
                'Book and view personal appointments',
                'Access personal dental treatment records',
                'View personal billing invoices & payment receipts',
                'Manage personal profile & medical questionnaire',
            ],
        ],
    ];

    /**
     * Enforce administrator and dentist access control.
     */
    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'dentist'])) {
            abort(403, 'Access denied. User management and security credentials are restricted to clinic administrators and attending dentists.');
        }
    }

    /**
     * Display a listing of authorized users and access control directory.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = User::with('patient:id,user_id,patient_id')
            ->search($request->search)
            ->roleFilter($request->role)
            ->statusFilter($request->status);

        if ($request->filled('verified')) {
            if ($request->verified === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->verified === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        $users = $query->orderByRaw("CASE 
                WHEN role = 'admin' THEN 1 
                WHEN role = 'dentist' THEN 2 
                WHEN role = 'staff' THEN 3 
                WHEN role = 'secretary' THEN 4 
                ELSE 5 END")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total_users' => User::count(),
            'staff_count' => User::whereIn('role', ['admin', 'dentist', 'staff', 'secretary'])->count(),
            'active_count' => User::where('is_active', true)->count(),
            'suspended_count' => User::where('is_active', false)->count(),
            'patient_count' => User::where('role', 'patient')->count(),
        ];

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status', 'verified']),
            'metrics' => $metrics,
            'roleDefinitions' => $this->roleDefinitions,
        ]);
    }

    /**
     * Store a newly created authorized user in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'nullable|string|max:50',
            'role' => ['required', Rule::in(['admin', 'dentist', 'staff', 'secretary', 'patient'])],
            'password' => ['required', 'string', Password::defaults()],
            'email_verified' => 'boolean',
        ]);

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'role' => $validated['role'],
            'is_active' => true,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => !empty($validated['email_verified']) ? now() : null,
        ]);

        if ($newUser->role === 'patient') {
            $newUser->ensurePatientProfile();
        }

        AuditLogger::log(
            module: 'users',
            action: 'created',
            description: "Admin created new authorized user {$newUser->name} ({$newUser->email}) with role '{$newUser->role}'.",
            model: $newUser,
            newValues: $newUser->only(['name', 'email', 'phone_number', 'role', 'is_active'])
        );

        return redirect()->route('users.index')
            ->with('success', "Authorized user '{$newUser->name}' created successfully with role: " . ucfirst($newUser->role) . ".");
    }

    /**
     * Update user details and access role.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        // Safeguard: Prevent admin/dentist from demoting self
        if ($user->id === Auth::id() && !in_array($request->role, ['admin', 'dentist'])) {
            return back()->with('error', 'You cannot change your own administrator/dentist role to prevent accidental lockout.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => 'nullable|string|max:50',
            'role' => ['required', Rule::in(['admin', 'dentist', 'staff', 'secretary', 'patient'])],
            'is_active' => 'boolean',
        ]);

        $oldValues = $user->only(['name', 'email', 'phone_number', 'role', 'is_active']);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log(
            module: 'users',
            action: 'updated',
            description: "Admin updated account credentials and permissions for {$user->name} ({$user->email}).",
            model: $user,
            oldValues: $oldValues,
            newValues: $user->only(['name', 'email', 'phone_number', 'role', 'is_active'])
        );

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Toggle active/suspended status for a user.
     */
    public function toggleStatus(Request $request, User $user)
    {
        $this->authorizeAdmin();

        // Safeguard: Prevent admin from suspending self
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own administrator account.');
        }

        $wasActive = $user->is_active;
        $user->update(['is_active' => !$wasActive]);

        $statusStr = $user->is_active ? 'activated' : 'suspended';

        AuditLogger::log(
            module: 'users',
            action: 'status_changed',
            description: "Admin {$statusStr} account access for {$user->name} ({$user->email}).",
            model: $user,
            oldValues: ['is_active' => $wasActive],
            newValues: ['is_active' => $user->is_active]
        );

        return back()->with('success', "User account for '{$user->name}' has been {$statusStr}.");
    }

    /**
     * Reset password for an authorized user.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLogger::log(
            module: 'users',
            action: 'password_reset',
            description: "Admin manually reset security password for {$user->name} ({$user->email}).",
            model: $user
        );

        return back()->with('success', "Password for user '{$user->name}' has been successfully reset.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        // Safeguard: Cannot delete self
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own administrator account.');
        }

        // Safeguard: Cannot delete dentist with existing clinical records
        if ($user->role === 'dentist') {
            $hasAppointments = $user->dentistAppointments()->exists();
            $hasTreatments = $user->dentistTreatments()->exists();

            if ($hasAppointments || $hasTreatments) {
                return back()->with('error', "Cannot delete Dr. {$user->name}: This dentist has linked patient appointments or treatments. Deactivate the account instead to maintain clinical record integrity.");
            }
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $userRole = $user->role;

        AuditLogger::log(
            module: 'users',
            action: 'deleted',
            description: "Admin permanently deleted user account {$userName} ({$userEmail}, Role: {$userRole}).",
            model: $user,
            oldValues: $user->only(['name', 'email', 'role', 'is_active'])
        );

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User account '{$userName}' has been deleted.");
    }
}

