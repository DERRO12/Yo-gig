@extends('layouts.app')

@section('title', 'Find Gigs')

@section('content')
    <div class="container dash">
        <div class="dash-header">
            <div>
                <h1>Available gigs</h1>
                <p>Every gig here is already paid into escrow. Your payout is guaranteed once the client approves.</p>
            </div>
        </div>

        @include('maid.partials.nav')

        <form method="GET" class="card card-body mb-3">
            <div class="flex-between">
                <input type="search" name="q" class="input" value="{{ request('q') }}" placeholder="Search by title or location" style="flex: 1; min-width: 200px;">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        @if ($areaGigs->isNotEmpty())
            <h2 class="card-title mb-2">📍 Near you in {{ $maidArea }} ({{ $areaGigs->count() }})</h2>
            <div class="grid grid-3 mb-3">
                @foreach ($areaGigs as $gig)
                    @include('maid.partials.gig-card', ['gig' => $gig, 'inArea' => true])
                @endforeach
            </div>
        @endif

        <h2 class="card-title mb-2">{{ $areaGigs->isNotEmpty() ? 'Other gigs' : 'All gigs' }} ({{ $otherGigs->count() }})</h2>
        @if ($otherGigs->isEmpty())
            <div class="card empty">
                <div class="empty__icon">🔍</div>
                <h3>No gigs found</h3>
                <p>New funded gigs are posted daily. Check back soon.</p>
            </div>
        @else
            <div class="grid grid-3">
                @foreach ($otherGigs as $gig)
                    @include('maid.partials.gig-card', ['gig' => $gig])
                @endforeach
            </div>
        @endif
    </div>
@endsection
