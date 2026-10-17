@extends('layouts.app')

@section('title', 'Create an account')

@section('content')
    @php($activeTab = old('national_id_number') !== null || request('role') === 'maid' ? 'maid' : 'client')

    <section class="auth">
        <div class="container">
            <div class="card auth-card">
                <h1>Join {{ $site->get('site_name') }}</h1>
                <p class="text-center text-muted mt-1">Choose how you want to use the platform.</p>

                <div class="segmented" data-tabs="{{ $activeTab }}" role="tablist">
                    <button type="button" data-tab="client" class="{{ $activeTab === 'client' ? 'is-active' : '' }}">I want to hire</button>
                    <button type="button" data-tab="maid" class="{{ $activeTab === 'maid' ? 'is-active' : '' }}">I want to work</button>
                </div>

                {{-- Client --}}
                <form action="{{ route('register.client') }}" method="POST" class="form-panel {{ $activeTab === 'client' ? 'is-active' : '' }}" data-tab-panel="client">
                    @csrf
                    <div class="form-group">
                        <label for="client_name">Full name</label>
                        <input type="text" name="name" id="client_name" class="input" value="{{ old('name') }}" autocomplete="name" required>
                    </div>
                    <div class="form-group">
                        <label for="client_email">Email address</label>
                        <input type="email" name="email" id="client_email" class="input" value="{{ old('email') }}" autocomplete="email" required>
                    </div>
                    <div class="form-group">
                        <label for="client_phone">Phone number</label>
                        <input type="tel" name="phone_number" id="client_phone" class="input" value="{{ old('phone_number') }}" placeholder="+256700000000" autocomplete="tel" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="client_password">Password</label>
                            <input type="password" name="password" id="client_password" class="input" autocomplete="new-password" required>
                        </div>
                        <div class="form-group">
                            <label for="client_password_confirmation">Confirm password</label>
                            <input type="password" name="password_confirmation" id="client_password_confirmation" class="input" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Create client account</button>
                </form>

                {{-- Maid --}}
                <form action="{{ route('register.maid') }}" method="POST" enctype="multipart/form-data" class="form-panel {{ $activeTab === 'maid' ? 'is-active' : '' }}" data-tab-panel="maid">
                    @csrf
                    <div class="alert alert--info small">🛡️ We verify every maid's national ID before they can take paid gigs.</div>
                    <div class="form-group">
                        <label for="maid_name">Full name</label>
                        <input type="text" name="name" id="maid_name" class="input" value="{{ old('name') }}" autocomplete="name" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="maid_email">Email address</label>
                            <input type="email" name="email" id="maid_email" class="input" value="{{ old('email') }}" autocomplete="email" required>
                        </div>
                        <div class="form-group">
                            <label for="maid_phone">Phone number</label>
                            <input type="tel" name="phone_number" id="maid_phone" class="input" value="{{ old('phone_number') }}" placeholder="+256700000000" autocomplete="tel" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="service_area">Area you work in</label>
                        <input type="text" name="service_area" id="service_area" class="input" value="{{ old('service_area') }}" placeholder="e.g. Ntinda">
                        <span class="form-hint">Gigs in your area are shown first.</span>
                    </div>
                    <div class="form-group">
                        <label for="national_id_number">National ID number (NIN)</label>
                        <input type="text" name="national_id_number" id="national_id_number" class="input" value="{{ old('national_id_number') }}" placeholder="CM1234567890" required>
                    </div>
                    <div class="form-group">
                        <label for="national_id_photo">Photo of your national ID</label>
                        <input type="file" name="national_id_photo" id="national_id_photo" class="input" accept="image/jpeg,image/png" data-preview="id-preview" required>
                        <span class="form-hint">JPG or PNG, max 2 MB. Only our verification team can see it.</span>
                        <div class="image-preview" id="id-preview"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="maid_password">Password</label>
                            <input type="password" name="password" id="maid_password" class="input" autocomplete="new-password" required>
                        </div>
                        <div class="form-group">
                            <label for="maid_password_confirmation">Confirm password</label>
                            <input type="password" name="password_confirmation" id="maid_password_confirmation" class="input" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent btn-block btn-lg">Apply as a maid</button>
                </form>

                <p class="text-center small text-muted mt-3">
                    Already have an account? <a href="{{ route('login') }}" class="fw-bold">Log in</a>
                </p>
            </div>
        </div>
    </section>
@endsection
