<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | YO'gig Maid</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .auth-wrapper {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-color: var(--light-bg);
        }
        .auth-card {
            background: #ffffff;
            border-radius: var(--radius-lg, 12px);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            box-shadow: var(--shadow-md, 0 4px 12px rgba(0,0,0,0.1));
            border: 1px solid #E2E8F0;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark, #0F172A);
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary, #0F4C5C);
        }
        .alert-danger {
            background-color: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
        }
        .btn-full {
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
        }
    </style>
</head>
<body>

    @include('components.navbar')

    <div class="auth-wrapper">
        <div class="auth-card">
            <h2 class="section-title text-center" style="margin-bottom: 0.5rem;">Welcome Back</h2>
            <p class="section-subtitle text-center" style="margin-bottom: 2rem;">Log in with your Email address or Phone number</p>

            @if ($errors->any())
                <div class="alert-danger">
                    <ul style="margin: 0; padding-left: 1.2rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="login">Email Address or Phone Number</label>
                    <input type="text" name="login" id="login" class="form-control" placeholder="e.g. client@example.com or +256700000000" value="{{ old('login') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full" style="margin-top: 1rem;">Log In</button>

                <p class="text-center" style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted, #64748B);">
                    Don't have an account? <a href="{{ route('register') }}" style="color: var(--primary, #0F4C5C); font-weight: 700;">Register here</a>
                </p>
            </form>
        </div>
    </div>

    @include('components.footer')

</body>
</html>