@extends('layouts.app')

@section('title', 'Maid Dashboard')

@section('content')
    <div class="container dash">
        <div class="dash-header">
            <div>
                <h1>Welcome, {{ explode(' ', trim($maid->name))[0] }} 👋</h1>
                <p>
                    @if ($maid->maidProfile?->isVerified()) ✅ Verified maid @else ⏳ Verification pending @endif
                    @if ($maid->maidProfile?->service_area) · 📍 {{ $maid->maidProfile->service_area }} @endif
                    @if ($maid->maidProfile?->average_rating) · ★ {{ number_format($maid->maidProfile->average_rating, 1) }} @endif
                </p>
            </div>
            <a href="{{ route('maid.gigs.index') }}" class="btn btn-accent">Find gigs</a>
        </div>

        @include('maid.partials.nav')

        <div class="stats">
            <div class="stat"><div class="stat__label">Open gigs</div><div class="stat__value">{{ $stats['openGigs'] }}</div></div>
            <div class="stat"><div class="stat__label">My active jobs</div><div class="stat__value">{{ $stats['activeJobs'] }}</div></div>
            <div class="stat stat--accent"><div class="stat__label">Pending payouts</div><div class="stat__value">{{ $site->money($stats['pendingPayouts']) }}</div></div>
            <div class="stat stat--success"><div class="stat__label">Total earned</div><div class="stat__value">{{ $site->money($stats['totalEarned']) }}</div></div>
        </div>

        @if (! $maid->maidProfile?->hasPayoutDetails())
            <div class="alert alert--info">💸 <span>Add your mobile money or bank details on the <a href="{{ route('maid.earnings') }}">Earnings page</a> so we can pay you.</span></div>
        @endif

        <div class="detail-grid">
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">My jobs</h2>
                    <span class="small text-muted">{{ $activeJobs->count() + $awaitingApproval->count() }} active</span>
                </div>

                @forelse ($activeJobs->concat($awaitingApproval) as $job)
                    <div class="booking-row">
                        <div>
                            <a href="{{ route('maid.gigs.show', $job) }}" class="booking-row__title">{{ $job->title }}</a>
                            <div class="small text-muted">📍 {{ $job->location }} @if ($job->scheduleLabel()) · 📅 {{ $job->scheduleLabel() }} @endif</div>
                            <div class="booking-row__badges mt-1">
                                <x-job-status :job="$job" />
                                <span class="badge">👤 {{ $job->client?->name }}</span>
                            </div>
                        </div>
                        <div class="inline-actions">
                            <span class="price">{{ $site->money($job->maid_earnings ?? $job->budget) }}</span>
                            <a href="{{ route('maid.gigs.show', $job) }}" class="btn btn-ghost btn-sm">Open</a>
                        </div>
                    </div>
                @empty
                    <div class="empty">
                        <div class="empty__icon">🧺</div>
                        <h3>No active jobs</h3>
                        <p>Claim a funded gig to get started.</p>
                        <a href="{{ route('maid.gigs.index') }}" class="btn btn-primary">Browse gigs</a>
                    </div>
                @endforelse
            </div>

            <aside class="card">
                <div class="card-head">
                    <h2 class="card-title">Latest gigs</h2>
                    <a href="{{ route('maid.gigs.index') }}" class="small fw-bold">See all</a>
                </div>
                @forelse ($recentGigs as $gig)
                    <div class="booking-row" style="grid-template-columns: 1fr;">
                        <div>
                            <a href="{{ route('maid.gigs.show', $gig) }}" class="booking-row__title">{{ $gig->title }}</a>
                            <div class="small text-muted">📍 {{ $gig->location }}</div>
                            <div class="small fw-bold text-success">{{ $site->money($gig->maid_earnings ?? $gig->budget) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty"><p>No open gigs right now.</p></div>
                @endforelse
            </aside>
        </div>
    </div>
@endsection
