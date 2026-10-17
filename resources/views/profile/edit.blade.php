@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.app')

@section('title', 'My Profile')

@section('content')
    <div class="{{ $user->isAdmin() ? '' : 'container dash container-narrow' }}">
        <div class="dash-header">
            <div class="flex-between" style="justify-content: flex-start;">
                <span class="avatar avatar--lg">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <div>
                    <h1>My Profile</h1>
                    <p>{{ ucfirst($user->role) }} account · joined {{ $user->created_at->format('M Y') }}</p>
                </div>
            </div>
        </div>

        <div class="card card-body mb-3">
            <h2 class="card-title mb-2">Personal details</h2>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" class="input" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="input" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="input" value="{{ old('phone_number', $user->phone_number) }}" required>
                    </div>
                </div>

                @if ($user->isMaid() && $user->maidProfile)
                    <div class="form-group">
                        <label for="service_area">Service area</label>
                        <input type="text" id="service_area" name="service_area" class="input" value="{{ old('service_area', $user->maidProfile->service_area) }}" placeholder="e.g. Ntinda">
                    </div>
                    <div class="form-group">
                        <label for="bio">About you</label>
                        <textarea id="bio" name="bio" class="input" rows="3" placeholder="Your experience and skills">{{ old('bio', $user->maidProfile->bio) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox"><input type="checkbox" name="available" value="1" @checked(old('available', $user->maidProfile->available))> I'm available for new gigs</label>
                    </div>
                @endif

                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </div>

        <div class="card card-body">
            <h2 class="card-title mb-2">Change password</h2>
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" class="input" autocomplete="current-password" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">New password</label>
                        <input type="password" id="password" name="password" class="input" autocomplete="new-password" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="input" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-outline">Update password</button>
            </form>
        </div>
    </div>
@endsection
