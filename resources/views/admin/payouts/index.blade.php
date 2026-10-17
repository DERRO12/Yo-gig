@extends('layouts.admin')

@section('title', 'Maid payouts')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Maid payouts</h1>
            <p>Payouts are created when a client approves a job. Send the money, then record the transaction reference here.</p>
        </div>
    </div>

    <nav class="tabs">
        @foreach (['pending' => 'To send', 'paid' => 'Paid', 'failed' => 'Failed', 'all' => 'All'] as $value => $label)
            <a href="{{ route('admin.payouts.index', ['status' => $value]) }}" class="{{ $status === $value ? 'is-active' : '' }}">
                {{ $label }}
                @if ($value !== 'all' && ($counts[$value] ?? 0) > 0)
                    <span class="badge {{ $value === 'pending' ? 'badge--amber' : '' }}">{{ $counts[$value] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="card">
        <div class="table-wrap">
            <table class="table table--stack">
                <thead>
                    <tr><th>Maid</th><th>Booking</th><th>Send to</th><th>Amount</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        <tr>
                            <td data-label="Maid">
                                <a href="{{ route('admin.users.show', $payout->maid) }}" class="fw-bold">{{ $payout->maid?->name }}</a>
                                <div class="small text-muted">{{ $payout->maid?->phone_number }}</div>
                            </td>
                            <td data-label="Booking"><a href="{{ route('admin.bookings.show', $payout->job) }}">{{ $payout->job?->reference }}</a></td>
                            <td data-label="Send to">
                                @if ($payout->account_number)
                                    {{ $payout->methodLabel() }}<div class="small text-muted">{{ $payout->account_name }} · {{ $payout->account_number }}</div>
                                @else
                                    <span class="badge badge--red">No payout details</span>
                                @endif
                            </td>
                            <td data-label="Amount" class="fw-bold nowrap">{{ $site->money($payout->amount) }}</td>
                            <td data-label="Status">
                                <x-record-status :status="$payout->status" />
                                @if ($payout->reference)<div class="small text-muted">ref {{ $payout->reference }}</div>@endif
                            </td>
                            <td data-label="Action">
                                @if ($payout->status !== 'paid')
                                    <form action="{{ route('admin.payouts.paid', $payout) }}" method="POST" class="inline-actions">
                                        @csrf
                                        <input type="text" name="reference" class="input" placeholder="Transaction ref" required style="width: 150px; padding: 0.45rem 0.6rem; font-size: 0.85rem;">
                                        <button type="submit" class="btn btn-success btn-sm">Mark paid</button>
                                    </form>
                                    @if ($payout->status === 'pending')
                                        <form action="{{ route('admin.payouts.failed', $payout) }}" method="POST" class="mt-1" data-confirm="Mark this payout as failed?">
                                            @csrf
                                            <button type="submit" class="btn btn-danger-outline btn-sm">Failed</button>
                                        </form>
                                    @endif
                                @else
                                    <span class="small text-muted">{{ $payout->paid_at?->format('M j, Y') }} by {{ $payout->processor?->name }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Nothing here. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $payouts->links() }}
@endsection
