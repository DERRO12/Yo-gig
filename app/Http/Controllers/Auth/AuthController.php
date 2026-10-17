<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MaidProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle user login via Email OR Phone Number.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Determine if input is an email address or a phone number
        $fieldType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        if (Auth::attempt([$fieldType => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();

                return back()->withErrors([
                    'login' => 'Your account has been suspended. Please contact support.',
                ])->onlyInput('login');
            }

            $request->session()->regenerate();

            return redirect()->intended(route($user->dashboardRoute()));
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    /**
     * Handle Client Registration.
     */
    public function registerClient(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
        ]);

        Auth::login($user);

        return redirect()->route('client.dashboard')->with('success', 'Registration successful! Book your first cleaning below.');
    }

    /**
     * Handle Maid Registration.
     */
    public function registerMaid(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'national_id_number' => 'required|string|max:50|unique:maid_profiles',
            'national_id_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'service_area' => 'nullable|string|max:255',
        ]);

        // ID photos are private: stored on the local disk and only viewable by admins.
        $photoPath = $request->file('national_id_photo')->store('national_ids', 'local');

        $user = DB::transaction(function () use ($validated, $photoPath): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($validated['password']),
                'role' => 'maid',
            ]);

            MaidProfile::create([
                'user_id' => $user->id,
                'national_id_number' => $validated['national_id_number'],
                'national_id_photo_path' => $photoPath,
                'verification_status' => 'pending',
                'service_area' => $validated['service_area'] ?? null,
            ]);

            return $user;
        });

        Auth::login($user);

        return redirect()
            ->route('maid.dashboard')
            ->with('success', 'Account created! Your ID is pending admin verification.');
    }

    /**
     * Handle User Logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
