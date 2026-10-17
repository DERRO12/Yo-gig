<article class="card gig-card">
    <div class="flex-between">
        <h3 class="gig-card__title"><a href="{{ route('maid.gigs.show', $gig) }}">{{ $gig->title }}</a></h3>
        @if (! empty($inArea))
            <span class="badge badge--blue">Near you</span>
        @endif
    </div>
    <ul class="meta-list">
        <li><span class="ico">📍</span> {{ $gig->location }}</li>
        @if ($gig->scheduleLabel())
            <li><span class="ico">📅</span> {{ $gig->scheduleLabel() }}</li>
        @endif
        @if ($gig->service)
            <li><span class="ico">🧽</span> {{ $gig->service->name }} · {{ $gig->quantity }} {{ $gig->service->price_unit }}</li>
        @endif
    </ul>
    @if ($gig->description)
        <p class="small text-muted">{{ Str::limit($gig->description, 120) }}</p>
    @endif
    <div class="gig-card__foot">
        <span class="price">{{ $site->money($gig->maid_earnings ?? $gig->budget) }} <small>you earn</small></span>
        <div class="inline-actions">
            <a href="{{ route('maid.gigs.show', $gig) }}" class="btn btn-ghost btn-sm">Details</a>
            @if ($canClaim)
                <form action="{{ route('maid.gigs.claim', $gig) }}" method="POST" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Claim</button>
                </form>
            @endif
        </div>
    </div>
</article>
