@extends('layouts.app')

@section('title', $job->title)

@section('content')
    <div class="container dash">
        <div class="dash-header">
            <div>
                <a href="{{ $isMine ? route('maid.dashboard') : route('maid.gigs.index') }}" class="small">← Back</a>
                <h1>{{ $job->title }}</h1>
                <div class="booking-row__badges mt-1">
                    <span class="badge">{{ $job->reference }}</span>
                    <x-job-status :job="$job" />
                    <span class="badge badge--teal">🔒 Funded in escrow</span>
                </div>
            </div>
        </div>

        <div class="detail-grid">
            <div class="stack">
                <div class="card">
                    <div class="card-head"><h2 class="card-title">Gig details</h2></div>
                    <div class="card-body">
                        <dl class="dl">
                            <div><dt>Service</dt><dd>{{ $job->service?->name ?? '—' }}</dd></div>
                            <div><dt>Size</dt><dd>{{ $job->quantity }} {{ $job->service?->price_unit }}</dd></div>
                            <div><dt>When</dt><dd>{{ $job->scheduleLabel() ?? 'Flexible' }}</dd></div>
                            <div><dt>Where</dt><dd>{{ $job->location }}</dd></div>
                        </dl>
                        @if ($job->description)
                            <h3 class="small text-muted mt-2 mb-2" style="text-transform: uppercase;">Client instructions</h3>
                            <p style="white-space: pre-line;">{{ $job->description }}</p>
                        @endif
                        @if ($job->imageUrls())
                            <div class="thumbs thumbs--lg mt-2">
                                @foreach ($job->imageUrls() as $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Photo of the space"></a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @if ($isMine)
                    <div class="card card-body">
                        <h2 class="card-title mb-2">Client contact</h2>
                        <p><strong>{{ $job->client->name }}</strong></p>
                        <div class="inline-actions mt-1">
                            <a href="tel:{{ $job->client->phone_number }}" class="btn btn-ghost btn-sm">📞 {{ $job->client->phone_number }}</a>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $job->client->phone_number) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">💬 WhatsApp</a>
                        </div>
                    </div>
                @endif
            </div>

            <aside class="stack">
                <div class="card card-body">
                    <h2 class="card-title mb-2">Your earnings</h2>
                    <div class="money-lines">
                        <div><span>Client paid</span><span>{{ $site->money($job->budget) }}</span></div>
                        <div><span>Platform fee</span><span>− {{ $site->money($job->platform_fee) }}</span></div>
                        <div class="total"><span>You receive</span><span class="text-success">{{ $site->money($job->maid_earnings ?? $job->budget) }}</span></div>
                    </div>
                    <p class="small text-muted mt-2">{{ $site->get('payout_schedule_note') }}</p>

                    <div class="stack mt-2">
                        @if ($job->isOpen())
                            @if ($canClaim)
                                <form action="{{ route('maid.gigs.claim', $job) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-block btn-lg">Claim this gig</button>
                                </form>
                            @else
                                <div class="alert alert--warning small">Your ID must be verified before you can claim gigs.</div>
                            @endif
                        @elseif ($isMine && $job->isMatched())
                            <form action="{{ route('maid.gigs.start', $job) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block">▶ Start job</button>
                            </form>
                            <form action="{{ route('maid.gigs.release', $job) }}" method="POST" data-confirm="Release this gig back to other maids?">
                                @csrf
                                <button type="submit" class="btn btn-danger-outline btn-block">Release gig</button>
                            </form>
                        @elseif ($isMine && $job->isInProgress())
                            <form action="{{ route('maid.gigs.complete', $job) }}" method="POST" data-confirm="Mark this job as complete?">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block btn-lg">✓ Mark as complete</button>
                            </form>
                        @elseif ($isMine && $job->isAwaitingClientApproval())
                            <div class="alert alert--info small">Waiting for the client to approve. Your payout is created as soon as they do.</div>
                        @elseif ($isMine && $job->payout)
                            <div class="alert alert--success small">
                                Payout {{ $job->payout->status === 'paid' ? 'sent' : 'queued' }}: {{ $site->money($job->payout->amount) }}
                                @if ($job->payout->reference) (ref {{ $job->payout->reference }}) @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if ($isMine)
                    <div class="card card-body">
                        <h2 class="card-title mb-2">Progress</h2>
                        @include('partials.booking-tracker', ['job' => $job])
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection
