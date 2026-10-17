<header class="site-header">
    <nav class="container nav" aria-label="Main navigation">
        <a href="{{ route('landing') }}" class="nav__brand" aria-label="{{ $site->get('site_name') }} home">
            @if ($site->image('logo'))
                <img src="{{ $site->image('logo') }}" alt="{{ $site->get('site_name') }}" width="160" height="42">
            @else
                <span class="nav__brand-text">{{ $site->get('site_name') }}</span>
            @endif
        </a>

        <button type="button" class="nav__toggle" data-nav-toggle aria-expanded="false" aria-controls="site-menu" aria-label="Toggle navigation menu">
            <span></span><span></span><span></span>
        </button>

        <div class="nav__menu" id="site-menu" data-nav-menu>
            <ul class="nav__links">
                <li><a href="{{ route('landing') }}" class="nav__link {{ request()->routeIs('landing') ? 'is-active' : '' }}">Home</a></li>
                <li><a href="{{ route('services') }}" class="nav__link {{ request()->routeIs('services') ? 'is-active' : '' }}">Services</a></li>
                <li><a href="{{ route('about') }}" class="nav__link {{ request()->routeIs('about') ? 'is-active' : '' }}">About Us</a></li>
                <li><a href="{{ route('contact') }}" class="nav__link {{ request()->routeIs('contact') ? 'is-active' : '' }}">Contact</a></li>
            </ul>

            <div class="nav__actions">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Get Started</a>
                @endguest

                @auth
                    @php($user = auth()->user())

                    @if ($user->isClient())
                        <a href="{{ route('client.bookings.create') }}" class="btn btn-accent btn-sm">+ Book a Maid</a>
                    @elseif ($user->isMaid())
                        <a href="{{ route('maid.gigs.index') }}" class="btn btn-accent btn-sm">Find Gigs</a>
                    @elseif ($user->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm">Admin Panel</a>
                    @endif

                    <div class="user-menu" data-dropdown>
                        <button type="button" class="user-menu__trigger" data-dropdown-trigger aria-expanded="false" aria-haspopup="true">
                            <span class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <span>{{ explode(' ', trim($user->name))[0] }}</span>
                            <span aria-hidden="true">▾</span>
                        </button>

                        <div class="user-menu__panel" role="menu">
                            <a href="{{ route($user->dashboardRoute()) }}" class="user-menu__item" role="menuitem">Dashboard</a>
                            @if ($user->isMaid())
                                <a href="{{ route('maid.earnings') }}" class="user-menu__item" role="menuitem">Earnings & Payouts</a>
                            @endif
                            <a href="{{ route('profile.edit') }}" class="user-menu__item" role="menuitem">My Profile</a>
                            <div class="user-menu__divider"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="user-menu__item user-menu__item--danger" role="menuitem">Log out</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </nav>
</header>
