@extends('layouts.app')

@section('title', 'My Bookings')

@section('content')
    <div class="container dash">
        <div class="dash-header">
            <div>
                <h1>Hi, {{ explode(' ', trim(auth()->user()->name))[0] }} 👋</h1>
                <p>Track your bookings, payments and approvals in one place.</p>
            </div>
            <a href="{{ route('client.bookings.create') }}" class="btn btn-accent">+ New booking</a>
        </div>

        <div class="stats">
            <div class="stat"><div class="stat__label">Active bookings</div><div class="stat__value">{{ $stats['active'] }}</div></div>
            <div class="stat stat--accent"><div class="stat__label">Awaiting payment</div><div class="stat__value">{{ $stats['awaitingPayment'] }}</div></div>
            <div class="stat stat--accent"><div class="stat__label">Needs your approval</div><div class="stat__value">{{ $stats['awaitingApproval'] }}</div></div>
            <div class="stat stat--success"><div class="stat__label">Held in escrow</div><div class="stat__value">{{ $site->money($stats['inEscrow']) }}</div></div>
        </div>

        @if ($stats['awaitingApproval'] > 0)
            <div class="alert alert--warning">⭐ <span>A maid has finished a job for you. Review and approve it so they can be paid.</span></div>
        @endif

        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Your bookings</h2>
                <span class="small text-muted">{{ $bookings->count() }} total</span>
            </div>

            @forelse ($bookings as $booking)
                <div class="booking-row">
                    <div>
                        <a href="{{ route('client.bookings.show', $booking) }}" class="booking-row__title">{{ $booking->title }}</a>
                        <div class="small text-muted">{{ $booking->reference }} · 📍 {{ $booking->location }} @if ($booking->scheduleLabel()) · 📅 {{ $booking->scheduleLabel() }} @endif</div>
                        <div class="booking-row__badges mt-1">
                            <x-job-status :job="$booking" />
                            <x-payment-status :job="$booking" />
                            @if ($booking->assignedMaid)
                                <span class="badge">🧹 {{ $booking->assignedMaid->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="inline-actions">
                        <span class="price">{{ $site->money($booking->budget) }}</span>
                        @if ($booking->needsPayment())
                            <a href="{{ route('client.bookings.show', $booking) }}#pay" class="btn btn-accent btn-sm">Pay now</a>
                        @elseif ($booking->isAwaitingClientApproval())
                            <a href="{{ route('client.bookings.show', $booking) }}#approve" class="btn btn-success btn-sm">Approve</a>
                        @else
                            <a href="{{ route('client.bookings.show', $booking) }}" class="btn btn-ghost btn-sm">View</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty">
                    <div class="empty__icon">🏠</div>
                    <h3>No bookings yet</h3>
                    <p>Book your first cleaning in under two minutes.</p>
                    <a href="{{ route('client.bookings.create') }}" class="btn btn-primary">Book a maid</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
