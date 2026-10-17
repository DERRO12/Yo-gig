@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <section class="auth">
        <div class="container">
            <div class="card auth-card">
                <h1>Welcome back</h1>
                <p class="text-center text-muted mt-1 mb-3">Log in with your email or phone number.</p>

                <form action="{{ url('/login') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="login">Email or phone number</label>
                        <input type="text" name="login" id="login" class="input @error('login') is-invalid @enderror" value="{{ old('login') }}" placeholder="you@example.com or +2567..." autocomplete="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="input" autocomplete="current-password" required>
                    </div>
                    <div class="form-group">
                        <label class="checkbox"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Log in</button>
                </form>

                <p class="text-center small text-muted mt-3">
                    New to {{ $site->get('site_name') }}? <a href="{{ route('register') }}" class="fw-bold">Create an account</a>
                </p>
            </div>
        </div>
    </section>
@endsection
