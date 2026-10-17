<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Dashboard') | {{ $site->get('site_name') }} Admin</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="admin">
    @php
        $pendingPayments = \App\Models\Payment::query()->where('status', 'pending')->count();
        $pendingPayouts = \App\Models\Payout::query()->where('status', 'pending')->count();
        $pendingMaids = \App\Models\MaidProfile::query()->where('verification_status', 'pending')->count();
        $unreadMessages = \App\Models\ContactMessage::query()->whereNull('read_at')->count();

        $nav = [
            'Overview' => [
                ['admin.dashboard', 'admin.dashboard', '📊', 'Dashboard', 0],
            ],
            'Operations' => [
                ['admin.bookings.index', 'admin.bookings.*', '📋', 'Bookings', 0],
                ['admin.payments.index', 'admin.payments.*', '💳', 'Client payments', $pendingPayments],
                ['admin.payouts.index', 'admin.payouts.*', '💸', 'Maid payouts', $pendingPayouts],
                ['admin.users.index', 'admin.users.*', '👥', 'Users & maids', $pendingMaids],
                ['admin.messages.index', 'admin.messages.*', '✉️', 'Messages', $unreadMessages],
            ],
            'Website content' => [
                ['admin.services.index', 'admin.services.*', '🧽', 'Services & pricing', 0],
                ['admin.sections.index', 'admin.sections.*', '🖼️', 'Page content', 0],
                ['admin.testimonials.index', 'admin.testimonials.*', '⭐', 'Testimonials', 0],
                ['admin.settings.edit', 'admin.settings.*', '⚙️', 'Site settings', 0],
            ],
        ];
    @endphp

    <div class="admin-shell">
        <aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
            <div class="admin-brand">
                <img src="{{ $site->image('logo') }}" alt="{{ $site->get('site_name') }}">
                <span>Super Admin</span>
            </div>

            <nav class="admin-nav">
                @foreach ($nav as $group => $links)
                    <div class="admin-nav__group">{{ $group }}</div>
                    @foreach ($links as [$route, $pattern, $icon, $label, $count])
                        <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'is-active' : '' }}">
                            <span class="ico">{{ $icon }}</span> {{ $label }}
                            @if ($count > 0)
                                <span class="count">{{ $count }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="admin-sidebar__foot">
                <a href="{{ route('landing') }}" target="_blank" rel="noopener">↗ View website</a>
            </div>
        </aside>
        <div class="admin-backdrop" data-sidebar-toggle></div>

        <div class="admin-main">
            <header class="admin-topbar">
                <button type="button" class="nav__toggle" data-sidebar-toggle aria-controls="admin-sidebar" aria-label="Open menu">
                    <span></span><span></span><span></span>
                </button>
                <div class="admin-topbar__title">@yield('title', 'Dashboard')</div>

                <div class="user-menu" data-dropdown style="position: relative;">
                    <button type="button" class="user-menu__trigger" data-dropdown-trigger aria-expanded="false" style="width: auto;">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span aria-hidden="true">▾</span>
                    </button>
                    <div class="user-menu__panel" style="position: absolute; right: 0; top: calc(100% + 0.5rem); min-width: 200px; box-shadow: var(--shadow-lg); z-index: 50;">
                        <div class="user-menu__item" style="cursor: default;"><strong>{{ auth()->user()->name }}</strong><br><span class="small text-muted">{{ auth()->user()->email }}</span></div>
                        <div class="user-menu__divider"></div>
                        <a href="{{ route('profile.edit') }}" class="user-menu__item">My profile</a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="user-menu__item user-menu__item--danger">Log out</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                @if (session('success'))
                    <div class="alert alert--success" role="status">✅ <span>{{ session('success') }}</span></div>
                @endif
                @if (session('error'))
                    <div class="alert alert--error" role="alert">⚠️ <span>{{ session('error') }}</span></div>
                @endif
                @if ($errors->any())
                    <div class="alert alert--error" role="alert">
                        <div>
                            <strong>Please fix the following:</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
</body>
</html>
