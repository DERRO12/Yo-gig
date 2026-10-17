@extends('layouts.app')

@section('title', 'Earnings & Payouts')

@section('content')
    <div class="container dash">
        <div class="dash-header">
            <div>
                <h1>Earnings & payouts</h1>
                <p>{{ $site->get('payout_schedule_note') }}</p>
            </div>
        </div>

        @include('maid.partials.nav')

        <div class="stats">
            <div class="stat stat--success"><div class="stat__label">Paid to you</div><div class="stat__value">{{ $site->money($totals['paid']) }}</div></div>
            <div class="stat stat--accent"><div class="stat__label">Waiting to be sent</div><div class="stat__value">{{ $site->money($totals['pending']) }}</div></div>
            <div class="stat"><div class="stat__label">Payouts</div><div class="stat__value">{{ $totals['count'] }}</div></div>
            <div class="stat"><div class="stat__label">Jobs completed</div><div class="stat__value">{{ $maid->maidProfile?->completed_jobs ?? 0 }}</div></div>
        </div>

        <div class="detail-grid">
            <div class="card">
                <div class="card-head"><h2 class="card-title">Payout history</h2></div>
                @if ($payouts->isEmpty())
                    <div class="empty">
                        <div class="empty__icon">💸</div>
                        <h3>No payouts yet</h3>
                        <p>Complete a job and get the client's approval to receive your first payout.</p>
                    </div>
                @else
                    <div class="table-wrap">
                        <table class="table table--stack">
                            <thead>
                                <tr><th>Job</th><th>Amount</th><th>Status</th><th>Reference</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($payouts as $payout)
                                    <tr>
                                        <td data-label="Job">
                                            <a href="{{ route('maid.gigs.show', $payout->job) }}" class="fw-bold">{{ $payout->job?->title }}</a>
                                            <div class="small text-muted">{{ $payout->created_at->format('M j, Y') }}</div>
                                        </td>
                                        <td data-label="Amount" class="fw-bold nowrap">{{ $site->money($payout->amount) }}</td>
                                        <td data-label="Status"><x-record-status :status="$payout->status" /></td>
                                        <td data-label="Reference" class="small">{{ $payout->reference ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <aside class="card card-body">
                <h2 class="card-title mb-2">Where should we pay you?</h2>
                <form action="{{ route('maid.earnings.payout-details') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="payout_method">Payout method</label>
                        <select id="payout_method" name="payout_method" class="input" required>
                            @foreach ($payoutMethods as $value => $label)
                                <option value="{{ $value }}" @selected(old('payout_method', $maid->maidProfile?->payout_method) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payout_account_name">Account name</label>
                        <input type="text" id="payout_account_name" name="payout_account_name" class="input" value="{{ old('payout_account_name', $maid->maidProfile?->payout_account_name ?? $maid->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="payout_account_number">Mobile money number or bank account</label>
                        <input type="text" id="payout_account_number" name="payout_account_number" class="input" value="{{ old('payout_account_number', $maid->maidProfile?->payout_account_number) }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Save payout details</button>
                </form>
            </aside>
        </div>
    </div>
@endsection
