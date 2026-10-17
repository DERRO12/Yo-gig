@extends('layouts.admin')

@section('title', 'Booking '.$job->reference)

@section('content')
    <div class="dash-header">
        <div>
            <a href="{{ route('admin.bookings.index') }}" class="small">← All bookings</a>
            <h1>{{ $job->title }}</h1>
            <div class="booking-row__badges mt-1">
                <span class="badge">{{ $job->reference }}</span>
                <x-job-status :job="$job" />
                <x-payment-status :job="$job" />
            </div>
        </div>
        <form action="{{ route('admin.bookings.destroy', $job) }}" method="POST" data-confirm="Permanently delete this booking and its payment records?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger-outline btn-sm">Delete booking</button>
        </form>
    </div>

    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-head"><h2 class="card-title">Details</h2></div>
                <div class="card-body">
                    <dl class="dl">
                        <div><dt>Service</dt><dd>{{ $job->service?->name ?? '—' }}</dd></div>
                        <div><dt>Quantity</dt><dd>{{ $job->quantity }} {{ $job->service?->price_unit }}</dd></div>
                        <div><dt>Scheduled</dt><dd>{{ $job->scheduleLabel() ?? '—' }}</dd></div>
                        <div><dt>Location</dt><dd>{{ $job->location }}</dd></div>
                        <div><dt>Client</dt><dd><a href="{{ route('admin.users.show', $job->client) }}">{{ $job->client->name }}</a><br><span class="small text-muted">{{ $job->client->phone_number }}</span></dd></div>
                        <div><dt>Maid</dt><dd>
                            @if ($job->assignedMaid)
                                <a href="{{ route('admin.users.show', $job->assignedMaid) }}">{{ $job->assignedMaid->name }}</a><br><span class="small text-muted">{{ $job->assignedMaid->phone_number }}</span>
                            @else
                                Not assigned
                            @endif
                        </dd></div>
                    </dl>
                    @if ($job->description)
                        <p class="mt-2" style="white-space: pre-line;">{{ $job->description }}</p>
                    @endif
                    @if ($job->imageUrls())
                        <div class="thumbs mt-2">
                            @foreach ($job->imageUrls() as $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Job photo"></a>
                            @endforeach
                        </div>
                    @endif
                    @if ($job->client_rating)
                        <p class="mt-2"><strong>Client rating:</strong> <span style="color: var(--accent);">{{ str_repeat('★', $job->client_rating) }}</span> {{ $job->client_review }}</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2 class="card-title">Client payments</h2></div>
                <div class="table-wrap">
                    <table class="table table--stack">
                        <thead><tr><th>Method</th><th>Transaction</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($job->payments as $payment)
                                <tr>
                                    <td data-label="Method">{{ $payment->methodLabel() }}<div class="small text-muted">{{ $payment->payer_phone }}</div></td>
                                    <td data-label="Transaction"><code>{{ $payment->transaction_reference }}</code><div class="small text-muted">{{ $payment->created_at->format('M j, g:i A') }}</div></td>
                                    <td data-label="Amount" class="nowrap">{{ $site->money($payment->amount) }}</td>
                                    <td data-label="Status"><x-record-status :status="$payment->status" /></td>
                                    <td data-label="">
                                        @if ($payment->isPending())
                                            <div class="inline-actions">
                                                <form action="{{ route('admin.payments.confirm', $payment) }}" method="POST">@csrf<button class="btn btn-success btn-sm">Confirm</button></form>
                                                <form action="{{ route('admin.payments.reject', $payment) }}" method="POST" data-confirm="Reject this payment?">@csrf<button class="btn btn-danger-outline btn-sm">Reject</button></form>
                                            </div>
                                        @elseif ($payment->verifier)
                                            <span class="small text-muted">by {{ $payment->verifier->name }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">The client has not submitted a payment yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <aside class="stack">
            <div class="card card-body">
                <h2 class="card-title mb-2">Money</h2>
                <div class="money-lines">
                    <div><span>Client pays</span><span>{{ $site->money($job->budget) }}</span></div>
                    <div><span>Platform commission</span><span>{{ $job->platform_fee !== null ? $site->money($job->platform_fee) : 'Set on payment' }}</span></div>
                    <div class="total"><span>Maid receives</span><span>{{ $job->maid_earnings !== null ? $site->money($job->maid_earnings) : '—' }}</span></div>
                </div>
                @if ($job->payout)
                    <div class="alert {{ $job->payout->status === 'paid' ? 'alert--success' : 'alert--warning' }} small mt-2">
                        Payout {{ $job->payout->status }}: {{ $site->money($job->payout->amount) }}
                        @if ($job->payout->reference) · ref {{ $job->payout->reference }} @endif
                        <a href="{{ route('admin.payouts.index', ['status' => $job->payout->status]) }}">Open payouts</a>
                    </div>
                @endif
            </div>

            @if (in_array($job->status, ['open', 'matched']))
                <div class="card card-body">
                    <h2 class="card-title mb-2">{{ $job->assignedMaid ? 'Reassign maid' : 'Assign a maid' }}</h2>
                    @if (! $job->isFunded())
                        <p class="small text-muted mb-2">Tip: this booking is not funded yet.</p>
                    @endif
                    <form action="{{ route('admin.bookings.assign', $job) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <select name="maid_id" class="input" required>
                                <option value="">Choose a verified maid</option>
                                @foreach ($maids as $maid)
                                    <option value="{{ $maid->id }}" @selected($job->maid_id === $maid->id)>{{ $maid->name }} ({{ $maid->phone_number }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-sm">Assign</button>
                    </form>
                </div>
            @endif

            <div class="card card-body">
                <h2 class="card-title mb-2">Admin actions</h2>
                <div class="stack">
                    @php
                        $actions = array_filter([
                            'start' => $job->isMatched() ? ['Mark in progress', 'btn-ghost', null] : null,
                            'unassign' => $job->isMatched() ? ['Remove maid', 'btn-ghost', 'Return this gig to the open pool?'] : null,
                            'complete' => $job->isInProgress() ? ['Mark completed', 'btn-ghost', null] : null,
                            'approve' => $job->isAwaitingClientApproval() ? ['Approve & release escrow', 'btn-success', 'Release escrow and create the maid payout on the client\'s behalf?'] : null,
                            'refund' => $job->payment_status === 'refund_due' ? ['Mark refunded', 'btn-accent', 'Confirm the client has been refunded?'] : null,
                            'cancel' => ! $job->isCompleted() && ! $job->isCancelled() ? ['Cancel booking', 'btn-danger-outline', 'Cancel this booking?'] : null,
                        ]);
                    @endphp
                    @forelse ($actions as $action => [$label, $style, $confirm])
                        <form action="{{ route('admin.bookings.status', $job) }}" method="POST" @if ($confirm) data-confirm="{{ $confirm }}" @endif>
                            @csrf
                            <input type="hidden" name="action" value="{{ $action }}">
                            <button type="submit" class="btn {{ $style }} btn-block btn-sm">{{ $label }}</button>
                        </form>
                    @empty
                        <p class="small text-muted">No actions available for this booking.</p>
                    @endforelse
                </div>
            </div>

            <div class="card card-body">
                <h2 class="card-title mb-2">Progress</h2>
                @include('partials.booking-tracker', ['job' => $job])
            </div>
        </aside>
    </div>
@endsection
