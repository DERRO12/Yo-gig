@extends('layouts.app')

@section('title', 'Services & Pricing')

@section('content')
    @include('partials.page-hero', ['hero' => $sections->get('hero')?->first()])

    <section class="section section--white">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Transparent pricing</span>
                <h2 class="section-title">Our Service Offerings</h2>
                <p class="section-lead">Pick a service, see the price upfront and pay into escrow. The maid is paid only after you approve the work.</p>
            </div>

            @if ($services->isEmpty())
                <div class="card empty">
                    <div class="empty__icon">🧹</div>
                    <h3>Services are being updated</h3>
                    <p>Please check back soon or contact us for a custom quote.</p>
                </div>
            @else
                <div class="grid grid-3">
                    @foreach ($services as $service)
                        @include('partials.service-card', ['service' => $service])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @php($steps = $sections->get('steps', collect()))
    @if ($steps->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Our guarantee</span>
                    <h2 class="section-title">How Every Booking Works</h2>
                </div>
                <div class="grid grid-4">
                    @foreach ($steps as $step)
                        <article class="card feature-card">
                            <span class="feature-card__label">{{ $step->badge }}</span>
                            <h3>{{ $step->title }}</h3>
                            <p>{{ $step->body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('partials.cta', ['cta' => $sections->get('cta')?->first()])
@endsection
