@extends('layouts.admin')

@section('title', 'Client payments')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Client payments</h1>
            <p>Check each transaction ID against your mobile money or bank statement, then confirm it to put the money in escrow.</p>
        </div>
    </div>

    <nav class="tabs">
        @foreach (['pending' => 'To verify', 'successful' => 'Confirmed', 'failed' => 'Rejected', 'refunded' => 'Refunded', 'all' => 'All'] as $value => $label)
            <a href="{{ route('admin.payments.index', ['status' => $value]) }}" class="{{ $status === $value ? 'is-active' : '' }}">
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
                    <tr><th>Booking</th><th>Client</th><th>Method</th><th>Transaction ID</th><th>Amount</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td data-label="Booking">
                                <a href="{{ route('admin.bookings.show', $payment->job) }}" class="fw-bold">{{ $payment->job?->reference }}</a>
                                <div class="small text-muted">{{ $payment->created_at->format('M j, g:i A') }}</div>
                            </td>
                            <td data-label="Client">{{ $payment->client?->name }}</td>
                            <td data-label="Method">{{ $payment->methodLabel() }}<div class="small text-muted">{{ $payment->payer_phone }}</div></td>
                            <td data-label="Transaction ID"><code>{{ $payment->transaction_reference }}</code></td>
                            <td data-label="Amount" class="fw-bold nowrap">{{ $site->money($payment->amount) }}</td>
                            <td data-label="Status"><x-record-status :status="$payment->status" /></td>
                            <td data-label="">
                                @if ($payment->isPending())
                                    <div class="inline-actions">
                                        <form action="{{ route('admin.payments.confirm', $payment) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Confirm</button>
                                        </form>
                                        <form action="{{ route('admin.payments.reject', $payment) }}" method="POST" data-confirm="Reject this payment? The client will be asked to pay again.">
                                            @csrf
                                            <input type="hidden" name="notes" value="Transaction could not be found">
                                            <button type="submit" class="btn btn-danger-outline btn-sm">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="small text-muted">{{ $payment->verifier ? 'by '.$payment->verifier->name : ($payment->status === 'successful' ? 'auto-confirmed' : '') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Nothing here. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $payments->links() }}
@endsection
