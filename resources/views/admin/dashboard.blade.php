@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Platform overview</h1>
            <p>Money in, money held, money out — at a glance.</p>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-primary btn-sm">All bookings</a>
    </div>

    <div class="stats">
        <div class="stat"><div class="stat__label">Client payments received</div><div class="stat__value">{{ $site->money($stats['revenue']) }}</div></div>
        <div class="stat stat--accent"><div class="stat__label">Held in escrow</div><div class="stat__value">{{ $site->money($stats['escrow']) }}</div></div>
        <div class="stat stat--success"><div class="stat__label">Platform commission</div><div class="stat__value">{{ $site->money($stats['commission']) }}</div></div>
        <div class="stat"><div class="stat__label">Paid out to maids</div><div class="stat__value">{{ $site->money($stats['paidOut']) }}</div></div>
    </div>

    <div class="stats">
        <div class="stat"><div class="stat__label">Bookings</div><div class="stat__value">{{ $stats['bookings'] }}</div></div>
        <div class="stat"><div class="stat__label">Clients</div><div class="stat__value">{{ $stats['clients'] }}</div></div>
        <div class="stat"><div class="stat__label">Maids</div><div class="stat__value">{{ $stats['maids'] }}</div></div>
        <div class="stat stat--accent"><div class="stat__label">Payouts owed</div><div class="stat__value">{{ $site->money($stats['pendingPayouts']) }}</div></div>
    </div>

    <div class="detail-grid">
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Recent bookings</h2>
                <a href="{{ route('admin.bookings.index') }}" class="small fw-bold">View all</a>
            </div>
            <div class="table-wrap">
                <table class="table table--stack">
                    <thead><tr><th>Booking</th><th>Client</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($recentBookings as $booking)
                            <tr>
                                <td data-label="Booking">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="fw-bold">{{ $booking->title }}</a>
                                    <div class="small text-muted">{{ $booking->reference }}</div>
                                </td>
                                <td data-label="Client">{{ $booking->client?->name }}</td>
                                <td data-label="Amount" class="nowrap">{{ $site->money($booking->budget) }}</td>
                                <td data-label="Status"><x-job-status :job="$booking" /> <x-payment-status :job="$booking" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No bookings yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h2 class="card-title">Needs your attention</h2></div>
                <div class="todo-list">
                    <a href="{{ route('admin.payments.index') }}">💳 Payments to verify <span class="badge {{ $todo['payments'] ? 'badge--amber' : '' }}">{{ $todo['payments'] }}</span></a>
                    <a href="{{ route('admin.payouts.index') }}">💸 Payouts to send <span class="badge {{ $todo['payouts'] ? 'badge--amber' : '' }}">{{ $todo['payouts'] }}</span></a>
                    <a href="{{ route('admin.users.index', ['role' => 'maid', 'verification' => 'pending']) }}">🛡️ Maids to verify <span class="badge {{ $todo['maids'] ? 'badge--amber' : '' }}">{{ $todo['maids'] }}</span></a>
                    <a href="{{ route('admin.bookings.index', ['payment' => 'refund_due']) }}">↩️ Refunds due <span class="badge {{ $todo['refunds'] ? 'badge--amber' : '' }}">{{ $todo['refunds'] }}</span></a>
                    <a href="{{ route('admin.messages.index') }}">✉️ Unread messages <span class="badge {{ $todo['messages'] ? 'badge--amber' : '' }}">{{ $todo['messages'] }}</span></a>
                </div>
            </div>

            <div class="card card-body">
                <h2 class="card-title mb-2">Bookings by status</h2>
                @php($maxCount = max(1, $statusCounts->max() ?? 1))
                @foreach (\App\Models\Job::STATUSES as $key => $label)
                    <div class="bar-row">
                        <span>{{ $label }}</span>
                        <div class="bar"><span style="width: {{ round(($statusCounts[$key] ?? 0) / $maxCount * 100) }}%"></span></div>
                        <strong>{{ $statusCounts[$key] ?? 0 }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
