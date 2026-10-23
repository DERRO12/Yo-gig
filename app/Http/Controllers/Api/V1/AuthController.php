<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ApiToken;
use App\Models\MaidProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Sign in with an email address or phone number and receive a bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        User::upgradeLegacyPasswordHash($credentials['login'], $credentials['password']);

        $user = User::query()->where(User::loginField($credentials['login']), $credentials['login'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages(['login' => 'The provided credentials do not match our records.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['login' => 'Your account has been suspended. Please contact support.']);
        }

        return $this->tokenResponse($user, $credentials['device_name'] ?? 'mobile');
    }

    public function registerClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
        ]);

        return $this->tokenResponse($user, 'mobile', 201);
    }

    public function registerMaid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'national_id_number' => ['required', 'string', 'max:50', 'unique:maid_profiles'],
            'national_id_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'service_area' => ['nullable', 'string', 'max:255'],
        ]);

        // ID photos are private: stored on the local disk and only viewable by admins.
        $photoPath = $request->file('national_id_photo')->store('national_ids', 'local');

        $user = DB::transaction(function () use ($validated, $photoPath): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($validated['password']),
                'role' => 'maid',
            ]);

            MaidProfile::query()->create([
                'user_id' => $user->id,
                'national_id_number' => $validated['national_id_number'],
                'national_id_photo_path' => $photoPath,
                'verification_status' => 'pending',
                'service_area' => $validated['service_area'] ?? null,
            ]);

            return $user;
        });

        return $this->tokenResponse($user, 'mobile', 201);
    }

    /**
     * Revoke the token used for this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    protected function tokenResponse(User $user, string $deviceName, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => ApiToken::issue($user, $deviceName),
            'user' => UserResource::make($user->load('maidProfile')),
        ], $status);
    }
}
