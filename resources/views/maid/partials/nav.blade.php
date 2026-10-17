<nav class="dash-nav" aria-label="Maid menu">
    <a href="{{ route('maid.dashboard') }}" class="{{ request()->routeIs('maid.dashboard') ? 'is-active' : '' }}">Overview</a>
    <a href="{{ route('maid.gigs.index') }}" class="{{ request()->routeIs('maid.gigs.*') ? 'is-active' : '' }}">Find gigs</a>
    <a href="{{ route('maid.earnings') }}" class="{{ request()->routeIs('maid.earnings') ? 'is-active' : '' }}">Earnings & payouts</a>
    <a href="{{ route('profile.edit') }}">Profile</a>
</nav>

@php($profile = auth()->user()->maidProfile)
@if ($profile && ! $profile->isVerified() && $site->bool('require_maid_verification'))
    <div class="alert {{ $profile->verification_status === 'rejected' ? 'alert--error' : 'alert--warning' }}">
        🛡️
        <span>
            @if ($profile->verification_status === 'rejected')
                Your ID verification was not approved. Please <a href="{{ route('contact') }}">contact support</a>.
            @else
                Your ID is pending verification. You can browse gigs now and claim them once our team approves you.
            @endif
        </span>
    </div>
@endif
