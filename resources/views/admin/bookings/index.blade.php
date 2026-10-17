@extends('layouts.admin')

@section('title', 'Bookings')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Bookings</h1>
            <p>Every gig on the platform with its job and payment status.</p>
        </div>
    </div>

    <form method="GET" class="filters">
        <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="Search reference, title, location or client">
        <select name="status" class="input">
            <option value="">All job statuses</option>
            @foreach (\App\Models\Job::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="payment" class="input">
            <option value="">All payment statuses</option>
            @foreach (\App\Models\Job::PAYMENT_STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(request('payment') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        @if (request()->hasAny(['q', 'status', 'payment']))
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        @endif
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table table--stack">
                <thead>
                    <tr><th>Booking</th><th>Client</th><th>Maid</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td data-label="Booking">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="fw-bold">{{ $booking->title }}</a>
                                <div class="small text-muted">{{ $booking->reference }} · {{ $booking->location }}</div>
                            </td>
                            <td data-label="Client">{{ $booking->client?->name }}</td>
                            <td data-label="Maid">{{ $booking->assignedMaid?->name ?? '—' }}</td>
                            <td data-label="Date" class="nowrap">{{ $booking->scheduled_date?->format('M j, Y') ?? '—' }}</td>
                            <td data-label="Amount" class="nowrap fw-bold">{{ $site->money($booking->budget) }}</td>
                            <td data-label="Status"><x-job-status :job="$booking" /> <x-payment-status :job="$booking" /></td>
                            <td data-label=""><a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-ghost btn-sm">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No bookings match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $bookings->links() }}
@endsection
