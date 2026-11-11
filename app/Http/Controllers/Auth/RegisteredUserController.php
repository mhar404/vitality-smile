<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'max_birth_date' => now()->subYears(3)->format('Y-m-d'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $maxBirthDate = now()->subYears(3)->format('Y-m-d');

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'date_of_birth' => ['required', 'date', 'before_or_equal:' . $maxBirthDate],
            'gender' => 'required|string|in:Male,Female,Other',
            'guardian' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if (!empty($request->date_of_birth)) {
                        try {
                            $age = \Carbon\Carbon::parse($request->date_of_birth)->age;
                            if ($age < 18 && empty(trim((string)$value))) {
                                $fail('A parent or legal guardian is required for patients under 18 years old.');
                            }
                        } catch (\Throwable $e) {}
                    }
                },
            ],
            'contact_number' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'address' => 'nullable|string|max:500',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'date_of_birth.required' => 'Please provide your date of birth.',
            'date_of_birth.before_or_equal' => 'Patients must be at least 3 years old to register. Patients 2 years old and below cannot register online.',
            'gender.in' => 'Please select a valid gender option.',
            'contact_number.required' => 'A contact number is required for patient clinic records.',
            'contact_number.regex' => 'The contact / mobile number must contain digits only.',
        ]);

        $fullName = trim("{$request->first_name} " . ($request->middle_name ? "{$request->middle_name} " : '') . "{$request->last_name}" . ($request->suffix ? " {$request->suffix}" : ''));

        $user = User::create([
            'name' => $fullName,
            'email' => $request->email,
            'role' => 'patient', // Open registration is for patients
            'password' => Hash::make($request->password),
        ]);

        // Generate sequential unique patient_id: PAT-YYYYMM-XXXX
        $countThisMonth = Patient::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
        $patientId = 'PAT-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);

        while (Patient::where('patient_id', $patientId)->exists()) {
            $countThisMonth++;
            $patientId = 'PAT-' . now()->format('Ym') . '-' . str_pad($countThisMonth, 4, '0', STR_PAD_LEFT);
        }

        Patient::create([
            'user_id' => $user->id,
            'patient_id' => $patientId,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'suffix' => $request->suffix,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'guardian' => $request->guardian,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'email' => $request->email,
            'status' => 'Active',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
