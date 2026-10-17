@extends('layouts.app')

@section('body_class', 'page-home')

@section('content')
    @php
        $slides = $sections->get('hero', collect());
        $steps = $sections->get('steps', collect());
        $cta = $sections->get('cta')?->first();
    @endphp

    {{-- Hero slider --}}
    @if ($slides->isNotEmpty())
        <section class="hero" data-slider aria-roledescription="carousel" aria-label="Highlights">
            @foreach ($slides as $slide)
                <article class="hero__slide {{ $loop->first ? 'is-active' : '' }}" data-slide aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $slides->count() }}">
                    <div class="hero__bg" style="background-image: url('{{ $slide->imageUrl('images/cashless.jpg') }}');"></div>
                    <div class="container hero__inner">
                        <div>
                            @if ($slide->badge)
                                <span class="hero__badge">✨ {{ $slide->badge }}</span>
                            @endif
                            <h1>{{ $slide->title }}</h1>
                            <p>{{ $slide->body }}</p>
                            <div class="hero__actions">
                                @if ($slide->button_text)
                                    <a href="{{ \App\Models\PageSection::link($slide->button_url) }}" class="btn btn-accent btn-lg">{{ $slide->button_text }}</a>
                                @endif
                                @if ($slide->secondary_button_text)
                                    <a href="{{ \App\Models\PageSection::link($slide->secondary_button_url) }}" class="btn btn-light btn-lg">{{ $slide->secondary_button_text }}</a>
                                @endif
                            </div>
                            <div class="hero__trust">
                                <div><strong>0% cash risk</strong><span>Escrow protected</span></div>
                                <div><strong>ID verified</strong><span>Every maid vetted</span></div>
                                <div><strong>Pay once</strong><span>Approve, then release</span></div>
                            </div>
                        </div>

                        @if ($steps->isNotEmpty())
                            <aside class="hero__card">
                                <h3>How your money is protected</h3>
                                <p>You pay {{ $site->get('site_name') }}, not a stranger. We hold it until you are happy.</p>
                                <ul>
                                    @foreach ($steps as $step)
                                        <li>{{ $step->title }}</li>
                                    @endforeach
                                </ul>
                            </aside>
                        @endif
                    </div>
                </article>
            @endforeach

            @if ($slides->count() > 1)
                <div class="hero__dots" role="tablist" aria-label="Choose slide">
                    @foreach ($slides as $slide)
                        <button type="button" class="hero__dot {{ $loop->first ? 'is-active' : '' }}" data-slide-to="{{ $loop->index }}" role="tab" aria-label="Show slide {{ $loop->iteration }}"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- How escrow works --}}
    @if ($steps->isNotEmpty())
        <section class="section" id="how-it-works">
            <div class="container">
                <div class="band" style="background-image: url('{{ asset('images/cashless.jpg') }}');">
                    <div class="section-head">
                        <span class="eyebrow" style="color: var(--accent);">Escrow payments</span>
                        <h2 class="section-title">How We Protect Both Sides</h2>
                        <p class="section-lead">Clients pay the platform. The platform pays the maid after you approve. Simple and dispute-free.</p>
                    </div>
                    <div class="grid grid-3">
                        @foreach ($steps as $step)
                            <div class="band-step">
                                <span class="step-num">{{ $step->badge ?: $loop->iteration }}</span>
                                <h3>{{ $step->title }}</h3>
                                <p>{{ $step->body }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Featured services --}}
    @if ($featuredServices->isNotEmpty())
        <section class="section section--white">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Services & pricing</span>
                    <h2 class="section-title">Popular Cleaning Services</h2>
                    <p class="section-lead">Transparent prices upfront. You always know what you pay before you book.</p>
                </div>
                <div class="grid grid-3">
                    @foreach ($featuredServices as $service)
                        @include('partials.service-card', ['service' => $service])
                    @endforeach
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('services') }}" class="btn btn-outline">See all services →</a>
                </div>
            </div>
        </section>
    @endif

    {{-- Fresh gigs --}}
    <section class="section" id="fresh-gigs">
        <div class="container">
            <div class="flex-between mb-3">
                <div class="section-head section-head--left" style="margin-bottom: 0;">
                    <span class="eyebrow">For maids</span>
                    <h2 class="section-title">Fresh Gigs, Already Paid For</h2>
                    <p class="section-lead">Every gig below is funded in escrow, so your payout is guaranteed.</p>
                </div>
                @if ($totalOpenGigs > 0)
                    <span class="badge badge--gold">🔥 {{ $totalOpenGigs }} open {{ Str::plural('gig', $totalOpenGigs) }}</span>
                @endif
            </div>

            @if ($freshGigs->isEmpty())
                <div class="card empty">
                    <div class="empty__icon">🧽</div>
                    <h3>No open gigs right now</h3>
                    <p>New funded gigs are posted daily. Register to be ready when they land.</p>
                    <a href="{{ route('register', ['role' => 'maid']) }}" class="btn btn-primary">Register as a Maid</a>
                </div>
            @else
                <div class="grid grid-3">
                    @foreach ($freshGigs as $gig)
                        <article class="card gig-card">
                            <div class="flex-between">
                                <h3 class="gig-card__title">{{ $gig->title }}</h3>
                                <span class="badge badge--teal">Funded</span>
                            </div>
                            <ul class="meta-list">
                                <li><span class="ico">📍</span> {{ $gig->location }}</li>
                                @if ($gig->scheduleLabel())
                                    <li><span class="ico">📅</span> {{ $gig->scheduleLabel() }}</li>
                                @endif
                                <li><span class="ico">🕒</span> Posted {{ $gig->created_at->diffForHumans() }}</li>
                            </ul>
                            <div class="gig-card__foot">
                                <span class="price">{{ $site->money($gig->maid_earnings ?? $gig->budget) }} <small>you earn</small></span>
                                @auth
                                    @if (auth()->user()->isMaid())
                                        <a href="{{ route('maid.gigs.show', $gig) }}" class="btn btn-primary btn-sm">View gig</a>
                                    @endif
                                @else
                                    <a href="{{ route('register', ['role' => 'maid']) }}" class="btn btn-primary btn-sm">Join to claim</a>
                                @endauth
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Testimonials --}}
    @if ($testimonials->isNotEmpty())
        <section class="section section--white">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Testimonials</span>
                    <h2 class="section-title">Trusted by Clients and Maids</h2>
                </div>
                <div class="grid grid-3">
                    @foreach ($testimonials as $testimonial)
                        <figure class="card testimonial">
                            <div class="testimonial__stars" aria-label="{{ $testimonial->rating }} out of 5 stars">{{ str_repeat('★', $testimonial->rating) }}{{ str_repeat('☆', 5 - $testimonial->rating) }}</div>
                            <blockquote class="testimonial__quote">“{{ $testimonial->quote }}”</blockquote>
                            <figcaption class="testimonial__who">
                                @if ($testimonial->photoUrl())
                                    <img src="{{ $testimonial->photoUrl() }}" alt="" loading="lazy">
                                @else
                                    <span class="avatar">{{ strtoupper(substr($testimonial->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <strong>{{ $testimonial->name }}</strong>
                                    <span>{{ $testimonial->role }}</span>
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('partials.cta', ['cta' => $cta])
@endsection
