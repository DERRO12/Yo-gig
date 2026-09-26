<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | YO'gig Maid</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .auth-wrapper {
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            background-color: var(--light-bg);
        }
        .auth-card {
            background: #ffffff;
            border-radius: var(--radius-lg, 12px);
            padding: 2.5rem;
            width: 100%;
            max-width: 520px;
            box-shadow: var(--shadow-md, 0 4px 12px rgba(0,0,0,0.1));
            border: 1px solid #E2E8F0;
        }
        .tab-group {
            display: flex;
            background-color: #F1F5F9;
            padding: 0.35rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .tab-btn {
            flex: 1;
            padding: 0.65rem 1rem;
            border: none;
            background: transparent;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-muted, #64748B);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tab-btn.active {
            background: #ffffff;
            color: var(--primary, #0F4C5C);
            box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.1));
        }
        .form-group {
            margin-bottom: 1.15rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: var(--text-dark, #0F172A);
            font-size: 0.875rem;
        }
        .form-control {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            font-size: 0.925rem;
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
        .form-tab {
            display: none;
        }
        .form-tab.active {
            display: block;
        }
    </style>
</head>
<body>

    @include('components.navbar')

    <div class="auth-wrapper">
        <div class="auth-card">
            <h2 class="section-title text-center" style="margin-bottom: 0.5rem;">Join YO'gig Maid</h2>
            <p class="section-subtitle text-center" style="margin-bottom: 1.5rem;">Select your account type to register</p>

            <!-- Role Selector Tabs -->
            <div class="tab-group">
                <button type="button" class="tab-btn active" onclick="switchTab('client')">I Want to Hire (Client)</button>
                <button type="button" class="tab-btn" onclick="switchTab('maid')">I Want to Work (Maid)</button>
            </div>

            @if ($errors->any())
                <div class="alert-danger">
                    <ul style="margin: 0; padding-left: 1.2rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Client Form -->
            <form id="clientForm" action="{{ route('register.client') }}" method="POST" class="form-tab active">
                @csrf
                <div class="form-group">
                    <label for="client_name">Full Name</label>
                    <input type="text" name="name" id="client_name" class="form-control" placeholder="e.g. John Doe" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="client_email">Email Address</label>
                    <input type="email" name="email" id="client_email" class="form-control" placeholder="john@example.com" value="{{ old('email') }}" required>
                </div>

                <div class="form-group">
                    <label for="client_phone">Phone Number</label>
                    <input type="text" name="phone_number" id="client_phone" class="form-control" placeholder="+256700000000" value="{{ old('phone_number') }}" required>
                </div>

                <div class="form-group">
                    <label for="client_password">Password</label>
                    <input type="password" name="password" id="client_password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="client_password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="client_password_confirmation" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full" style="margin-top: 1rem;">Register as Client</button>
            </form>

            <!-- Maid Form -->
            <form id="maidForm" action="{{ route('register.maid') }}" method="POST" enctype="multipart/form-data" class="form-tab">
                @csrf
                <div class="form-group">
                    <label for="maid_name">Full Name</label>
                    <input type="text" name="name" id="maid_name" class="form-control" placeholder="e.g. Jane Smith" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="maid_email">Email Address</label>
                    <input type="email" name="email" id="maid_email" class="form-control" placeholder="jane@example.com" value="{{ old('email') }}" required>
                </div>

                <div class="form-group">
                    <label for="maid_phone">Phone Number</label>
                    <input type="text" name="phone_number" id="maid_phone" class="form-control" placeholder="+256700000000" value="{{ old('phone_number') }}" required>
                </div>

                <div class="form-group">
                    <label for="national_id_number">National ID Number (NIN)</label>
                    <input type="text" name="national_id_number" id="national_id_number" class="form-control" placeholder="CM1234567890" value="{{ old('national_id_number') }}" required>
                </div>

                <div class="form-group">
                    <label for="national_id_photo">National ID Photo Copy</label>
                    <input type="file" name="national_id_photo" id="national_id_photo" class="form-control" accept="image/jpeg,image/png,image/jpg" required>
                </div>

                <div class="form-group">
                    <label for="maid_password">Password</label>
                    <input type="password" name="password" id="maid_password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="maid_password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="maid_password_confirmation" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-accent btn-full" style="margin-top: 1rem;">Register as Maid</button>
            </form>

            <p class="text-center" style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted, #64748B);">
                Already have an account? <a href="{{ route('login') }}" style="color: var(--primary, #0F4C5C); font-weight: 700;">Log in here</a>
            </p>
        </div>
    </div>

    @include('components.footer')

    <script>
        function switchTab(role) {
            const buttons = document.querySelectorAll('.tab-btn');
            const forms = document.querySelectorAll('.form-tab');

            buttons.forEach(btn => btn.classList.remove('active'));
            forms.forEach(form => form.classList.remove('active'));

            if (role === 'client') {
                buttons[0].classList.add('active');
                document.getElementById('clientForm').classList.add('active');
            } else {
                buttons[1].classList.add('active');
                document.getElementById('maidForm').classList.add('active');
            }
        }
    </script>

</body>
</html>