@php
    $socials = array_filter([
        'Facebook' => $site->get('social_facebook'),
        'X / Twitter' => $site->get('social_twitter'),
        'Instagram' => $site->get('social_instagram'),
        'LinkedIn' => $site->get('social_linkedin'),
    ]);
    $socialIcons = [
        'Facebook' => 'M24 12.07C24 5.41 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.32l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07',
        'X / Twitter' => 'M18.24 2.25h3.31l-7.23 8.26 8.5 11.24h-6.65l-5.21-6.82-5.97 6.82H1.68l7.73-8.84L1.25 2.25h6.83l4.71 6.23zm-1.16 17.52h1.83L7.08 4.13H5.12z',
        'Instagram' => 'M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.64-.07-4.85s.01-3.58.07-4.85C2.38 3.92 3.9 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 100 12.32 6.16 6.16 0 000-12.32zM12 16a4 4 0 110-8 4 4 0 010 8zm6.41-11.85a1.44 1.44 0 100 2.88 1.44 1.44 0 000-2.88z',
        'LinkedIn' => 'M19 0H5a5 5 0 00-5 5v14a5 5 0 005 5h14a5 5 0 005-5V5a5 5 0 00-5-5zM8 19H5V8h3v11zM6.5 6.73a1.76 1.76 0 110-3.53 1.76 1.76 0 010 3.53zM20 19h-3v-5.6c0-3.37-4-3.12-4 0V19h-3V8h3v1.77c1.4-2.59 7-2.78 7 2.47V19z',
    ];
@endphp

<footer class="footer">
    <div class="container footer__top">
        <div>
            <a href="{{ route('landing') }}" class="footer__logo">
                <img src="{{ $site->image('logo') }}" alt="{{ $site->get('site_name') }}" width="140" height="36">
            </a>
            <p class="footer__about">{{ $site->get('footer_about') }}</p>

            @if ($socials)
                <div class="social">
                    @foreach ($socials as $label => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $socialIcons[$label] }}"/></svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h4>Explore</h4>
            <ul class="footer__links">
                <li><a href="{{ route('landing') }}">Home</a></li>
                <li><a href="{{ route('services') }}">Services & Pricing</a></li>
                <li><a href="{{ route('about') }}">About Us</a></li>
                <li><a href="{{ route('contact') }}">Contact</a></li>
            </ul>
        </div>

        <div>
            <h4>Get Started</h4>
            <ul class="footer__links">
                <li><a href="{{ auth()->check() && auth()->user()->isClient() ? route('client.bookings.create') : route('register') }}">Book a Maid</a></li>
                <li><a href="{{ route('register', ['role' => 'maid']) }}">Become a Gig Maid</a></li>
                <li><a href="{{ route('landing') }}#how-it-works">How Escrow Works</a></li>
                <li><a href="{{ route('login') }}">Log in</a></li>
            </ul>
        </div>

        <div>
            <h4>Contact Us</h4>
            <ul class="footer__contact">
                @if ($site->get('contact_phone'))
                    <li>📞 <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site->get('contact_phone')) }}">{{ $site->get('contact_phone') }}</a></li>
                @endif
                @if ($site->get('contact_email'))
                    <li>✉️ <a href="mailto:{{ $site->get('contact_email') }}">{{ $site->get('contact_email') }}</a></li>
                @endif
                @if ($site->get('contact_address'))
                    <li>📍 <span>{{ $site->get('contact_address') }}</span></li>
                @endif
                @if ($site->get('business_hours'))
                    <li>🕒 <span>{{ $site->get('business_hours') }}</span></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="container footer__bottom">
        <p>&copy; {{ date('Y') }} <strong>{{ $site->get('site_name') }}</strong>. {{ $site->get('copyright_text') }}</p>
        <p>🔒 Payments held in escrow until you approve the work.</p>
    </div>
</footer>
