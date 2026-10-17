@extends('layouts.app')

@section('title', 'About Us')

@section('content')
    @php($hero = $sections->get('hero')?->first())
    @include('partials.page-hero', ['hero' => $hero])

    @php($mission = $sections->get('mission', collect()))
    @if ($mission->isNotEmpty())
        <section class="section section--white">
            <div class="container">
                <div class="grid grid-2">
                    @foreach ($mission as $card)
                        <article class="card feature-card">
                            <span class="feature-card__label">{{ $card->badge }}</span>
                            <h3>{{ $card->title }}</h3>
                            <p>{{ $card->body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @php($pillars = $sections->get('pillars', collect()))
    @if ($pillars->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Empowerment</span>
                    <h2 class="section-title">How We Support Our Gig Workers</h2>
                    <p class="section-lead">We do not just connect jobs. We equip maids with skills for long-term financial resilience.</p>
                </div>
                <div class="grid grid-4">
                    @foreach ($pillars as $pillar)
                        <article class="card feature-card">
                            <span class="step-num">{{ $pillar->badge ?: $loop->iteration }}</span>
                            <h3>{{ $pillar->title }}</h3>
                            <p>{{ $pillar->body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @php($stats = $sections->get('stats', collect()))
    @if ($stats->isNotEmpty())
        <section class="section section--tight">
            <div class="container">
                <div class="band" @if ($hero?->imageUrl()) style="background-image: url('{{ $hero->imageUrl() }}');" @endif>
                    <div class="section-head">
                        <h2 class="section-title">Targeted Economic Impact</h2>
                    </div>
                    <div class="grid grid-3 text-center">
                        @foreach ($stats as $stat)
                            <div class="band-step">
                                <div class="stat-big">{{ $stat->title }}</div>
                                <p>{{ $stat->body }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @include('partials.cta', ['cta' => $sections->get('cta')?->first()])
@endsection
