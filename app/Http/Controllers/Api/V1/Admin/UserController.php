<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PayoutResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::query()
                ->with('maidProfile')
                ->withCount(['jobsAsClient', 'jobsAsMaid'])
                ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
                ->when($request->input('verification'), fn ($query, $status) => $query->whereHas('maidProfile', fn ($q) => $q->where('verification_status', $status)))
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $term = '%'.$request->string('q').'%';
                    $query->where(fn ($q) => $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone_number', 'like', $term));
                })
                ->latest()
                ->paginate(20)
                ->withQueryString()
        );
    }

    public function show(User $user): UserResource
    {
        return $this->detail($user);
    }

    /**
     * Stream a maid's national ID photo; it is stored privately and only admins can view it.
     */
    public function nationalId(User $user): StreamedResponse
    {
        $path = $user->maidProfile?->national_id_photo_path;

        abort_if(blank($path), 404);

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404);
    }

    public function verify(Request $request, User $user): UserResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'verified', 'rejected'])],
        ]);

        abort_unless($user->isMaid() && $user->maidProfile, 404);

        $user->maidProfile->update(['verification_status' => $validated['status']]);

        return $this->detail($user, "Maid marked as {$validated['status']}.");
    }

    public function toggleActive(Request $request, User $user): UserResource
    {
        abort_if($user->is($request->user()), 422, 'You cannot suspend your own account.');

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $user->apiTokens()->delete();
        }

        return $this->detail($user, $user->is_active ? 'Account reactivated.' : 'Account suspended.');
    }

    protected function detail(User $user, ?string $message = null): UserResource
    {
        $user->load([
            'maidProfile',
            'jobsAsClient' => fn ($q) => $q->latest()->take(10),
            'jobsAsMaid' => fn ($q) => $q->latest()->take(10),
            'payouts' => fn ($q) => $q->latest()->take(10),
        ]);

        return UserResource::make($user)->additional(array_filter([
            'bookings_as_client' => BookingResource::collection($user->jobsAsClient),
            'bookings_as_maid' => BookingResource::collection($user->jobsAsMaid),
            'payouts' => PayoutResource::collection($user->payouts),
            'message' => $message,
        ]));
    }
}
