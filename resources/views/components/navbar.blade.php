<header class="navbar-header">
    
    <nav class="navbar" aria-label="Main Navigation">
        <div class="nav-container">
            
            <!-- 1. Brand / Logo Section -->
            <div class="nav-brand">
                <a href="{{ route('landing') }}" class="brand-logo" aria-label="YO'gig Maid Home">
                    <img src="{{ asset('images/logo.svg') }}" alt="YO'gig Maid Logo" class="navbar-logo-img" width="210" height="60">
                </a>
            </div>

            <!-- 2. Mobile Menu Toggle Button -->
            <button 
                type="button" 
                class="nav-toggle" 
                id="nav-toggle-btn" 
                aria-expanded="false" 
                aria-controls="nav-menu" 
                aria-label="Toggle navigation menu"
            >
                <span class="hamburger-icon" aria-hidden="true">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </span>
            </button>

            <!-- 3. Collapsible Navigation Container -->
            <div class="nav-menu-wrapper" id="nav-menu">
                
                <!-- Primary Navigation Links -->
                <ul class="nav-links" role="menubar">
                    <li role="none">
                        <a href="{{ route('landing') }}" 
                           class="nav-item {{ request()->routeIs('landing') ? 'active' : '' }}" 
                           role="menuitem">
                           Home
                        </a>
                    </li>
                    <li role="none">
                        <a href="{{ route('about') }}" 
                           class="nav-item {{ request()->routeIs('about') ? 'active' : '' }}" 
                           role="menuitem">
                           About Us
                        </a>
                    </li>
                    <li role="none">
                        <a href="{{ route('services') }}" 
                           class="nav-item {{ request()->routeIs('services') ? 'active' : '' }}" 
                           role="menuitem">
                           Services
                        </a>
                    </li>
                </ul>

                <!-- ================= ACTION AREA ================= -->
                <div class="nav-actions">

                    @guest
                        {{-- ---------- GUEST ---------- --}}
                        <a href="{{ route('login') }}" class="btn-link-accent">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-primary nav-btn">
                            Register
                        </a>
                    @endguest

                    @auth
                        {{-- ---------- LOGGED IN ---------- --}}

                        {{-- Role-specific primary CTA --}}
                        @if(auth()->user()->role === 'client')
                            <a href="{{ route('client.dashboard') }}" class="btn btn-primary nav-btn">
                                Book a Maid
                            </a>
                        @elseif(auth()->user()->role === 'maid')
                            <a href="{{ route('maid.dashboard') }}" class="btn btn-primary nav-btn">
                                My Jobs
                            </a>
                        @elseif(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary nav-btn">
                                Admin Panel
                            </a>
                        @endif

                        {{-- User dropdown --}}
                        <div class="nav-user-dropdown" id="user-dropdown">
                            <button 
                                type="button" 
                                class="nav-user-trigger" 
                                id="user-dropdown-btn"
                                aria-expanded="false"
                                aria-haspopup="true"
                            >
                                <span class="nav-user-avatar">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="nav-user-name">
                                    Hi, {{ explode(' ', trim(auth()->user()->name))[0] }}
                                </span>
                                <span class="nav-user-caret" aria-hidden="true">▾</span>
                            </button>

                            <div class="nav-user-menu" id="user-dropdown-menu" role="menu">
                                @if(auth()->user()->role === 'client')
                                    <a href="{{ route('client.dashboard') }}" class="nav-user-item" role="menuitem">
                                        My Dashboard
                                    </a>
                                @elseif(auth()->user()->role === 'maid')
                                    <a href="{{ route('maid.dashboard') }}" class="nav-user-item" role="menuitem">
                                        My Dashboard
                                    </a>
                                @elseif(auth()->user()->role === 'admin')
                                    <a href="{{ route('admin.dashboard') }}" class="nav-user-item" role="menuitem">
                                        Admin Dashboard
                                    </a>
                                @endif

                                <a href="{{ url('/profile') }}" class="nav-user-item" role="menuitem">
                                    My Profile
                                </a>

                                <div class="nav-user-divider"></div>

                                <form action="{{ route('logout') }}" method="POST" class="nav-user-logout-form">
                                    @csrf
                                    <button type="submit" class="nav-user-item nav-user-logout" role="menuitem">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth

                </div>
                <!-- =============== /ACTION AREA =============== -->

            </div>

        </div>
    </nav>
</header>


{{-- ============================================================ --}}
{{-- NAVBAR STYLES — dropdown + user pill (safe to move to CSS)   --}}
{{-- ============================================================ --}}
<style>
    /* ----- User pill trigger ----- */
    .nav-user-dropdown {
        position: relative;
        display: inline-block;
    }

    .nav-user-trigger {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.45rem 0.85rem;
        background: #F1F5F9;
        border: 1px solid #E2E8F0;
        border-radius: 999px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-dark, #0F172A);
        transition: background 0.2s ease, border-color 0.2s ease;
        font-family: inherit;
    }
    .nav-user-trigger:hover {
        background: #E2E8F0;
        border-color: #CBD5E1;
    }

    .nav-user-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--primary, #0F4C5C);
        color: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .nav-user-name {
        max-width: 100px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .nav-user-caret {
        font-size: 0.7rem;
        color: var(--text-muted, #64748B);
        transition: transform 0.2s ease;
    }
    .nav-user-dropdown.open .nav-user-caret {
        transform: rotate(180deg);
    }

    /* ----- Dropdown menu ----- */
    .nav-user-menu {
        position: absolute;
        top: calc(100% + 0.5rem);
        right: 0;
        min-width: 210px;
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        padding: 0.4rem;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-6px);
        transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s;
        z-index: 1000;
    }
    .nav-user-dropdown.open .nav-user-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .nav-user-item {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.6rem 0.85rem;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--text-dark, #0F172A);
        text-decoration: none;
        background: transparent;
        border: none;
        cursor: pointer;
        font-family: inherit;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .nav-user-item:hover {
        background: #F1F5F9;
        color: var(--primary, #0F4C5C);
    }

    .nav-user-divider {
        height: 1px;
        background: #E2E8F0;
        margin: 0.35rem 0.5rem;
    }

    .nav-user-logout-form {
        margin: 0;
    }
    .nav-user-logout {
        color: #B91C1C;
    }
    .nav-user-logout:hover {
        background: #FEF2F2;
        color: #991B1B;
    }

    /* ----- Mobile tweaks ----- */
    @media (max-width: 768px) {
        .nav-user-dropdown {
            width: 100%;
        }
        .nav-user-trigger {
            width: 100%;
            justify-content: flex-start;
        }
        .nav-user-name {
            max-width: none;
        }
        .nav-user-menu {
            position: static;
            opacity: 1;
            visibility: visible;
            transform: none;
            box-shadow: none;
            border: none;
            background: transparent;
            padding: 0;
            margin-top: 0.5rem;
            display: none;
        }
        .nav-user-dropdown.open .nav-user-menu {
            display: block;
        }
        .nav-user-item {
            padding-left: 1rem;
        }
    }
</style>


{{-- ============================================================ --}}
{{-- NAVBAR SCRIPTS — mobile toggle + dropdown behaviour          --}}
{{-- ============================================================ --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        /* ---------- Mobile hamburger toggle ---------- */
        const toggleBtn = document.getElementById('nav-toggle-btn');
        const navMenu   = document.getElementById('nav-menu');

        if (toggleBtn && navMenu) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                toggleBtn.setAttribute('aria-expanded', !isExpanded);
                toggleBtn.classList.toggle('is-active');
                navMenu.classList.toggle('active');
            });
        }

        /* ---------- User dropdown ---------- */
        const dropdown    = document.getElementById('user-dropdown');
        const dropdownBtn = document.getElementById('user-dropdown-btn');

        if (dropdown && dropdownBtn) {
            dropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = dropdown.classList.toggle('open');
                dropdownBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            // Close dropdown when clicking a menu item (except logout form button)
            dropdown.querySelectorAll('.nav-user-item').forEach(item => {
                item.addEventListener('click', () => {
                    if (item.tagName === 'A') {
                        dropdown.classList.remove('open');
                        dropdownBtn.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        }

        /* ---------- Click outside closes everything ---------- */
        document.addEventListener('click', (event) => {
            // Close mobile menu
            if (toggleBtn && navMenu &&
                !toggleBtn.contains(event.target) &&
                !navMenu.contains(event.target)) {
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.classList.remove('is-active');
                navMenu.classList.remove('active');
            }

            // Close user dropdown
            if (dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.remove('open');
                if (dropdownBtn) dropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });

        /* ---------- Esc key closes dropdown ---------- */
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && dropdown) {
                dropdown.classList.remove('open');
                if (dropdownBtn) dropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });
    });
</script>