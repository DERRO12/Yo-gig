@extends('layouts.admin')

@section('title', $user->name)

@section('content')
    <div class="dash-header">
        <div class="flex-between" style="justify-content: flex-start;">
            <span class="avatar avatar--lg">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <div>
                <a href="{{ route('admin.users.index') }}" class="small">← All users</a>
                <h1>{{ $user->name }}</h1>
                <div class="booking-row__badges">
                    <span class="badge badge--teal">{{ ucfirst($user->role) }}</span>
                    @if ($user->maidProfile)
                        <x-record-status :status="$user->maidProfile->verification_status" />
                    @endif
                    @unless ($user->is_active)<span class="badge badge--red">Suspended</span>@endunless
                </div>
            </div>
        </div>
        @unless ($user->is(auth()->user()))
            <form action="{{ route('admin.users.toggle-active', $user) }}" method="POST" data-confirm="{{ $user->is_active ? 'Suspend this account? They will be signed out.' : 'Reactivate this account?' }}">
                @csrf
                <button type="submit" class="btn {{ $user->is_active ? 'btn-danger-outline' : 'btn-success' }} btn-sm">{{ $user->is_active ? 'Suspend account' : 'Reactivate account' }}</button>
            </form>
        @endunless
    </div>

    <div class="detail-grid">
        <div class="stack">
            <div class="card card-body">
                <h2 class="card-title mb-2">Account</h2>
                <dl class="dl">
                    <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $user->phone_number }}</dd></div>
                    <div><dt>Joined</dt><dd>{{ $user->created_at->format('M j, Y') }}</dd></div>
                    @if ($user->maidProfile)
                        <div><dt>Service area</dt><dd>{{ $user->maidProfile->service_area ?? '—' }}</dd></div>
                        <div><dt>Completed jobs</dt><dd>{{ $user->maidProfile->completed_jobs }}</dd></div>
                        <div><dt>Rating</dt><dd>{{ $user->maidProfile->average_rating ? '★ '.number_format($user->maidProfile->average_rating, 1) : '—' }}</dd></div>
                        <div><dt>Payout to</dt><dd>{{ $user->maidProfile->payout_account_number ? \App\Models\Payment::METHODS[$user->maidProfile->payout_method] ?? $user->maidProfile->payout_method : 'Not set' }}<br><span class="small text-muted">{{ $user->maidProfile->payout_account_name }} {{ $user->maidProfile->payout_account_number }}</span></dd></div>
                    @endif
                </dl>
                @if ($user->maidProfile?->bio)
                    <p class="mt-2 text-muted">{{ $user->maidProfile->bio }}</p>
                @endif
            </div>

            @php($jobs = $user->isMaid() ? $user->jobsAsMaid : $user->jobsAsClient)
            @if (! $user->isAdmin())
                <div class="card">
                    <div class="card-head"><h2 class="card-title">{{ $user->isMaid() ? 'Recent jobs' : 'Recent bookings' }}</h2></div>
                    <div class="table-wrap">
                        <table class="table table--stack">
                            <thead><tr><th>Booking</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($jobs as $job)
                                    <tr>
                                        <td data-label="Booking"><a href="{{ route('admin.bookings.show', $job) }}" class="fw-bold">{{ $job->title }}</a><div class="small text-muted">{{ $job->reference }}</div></td>
                                        <td data-label="Date">{{ $job->scheduled_date?->format('M j, Y') }}</td>
                                        <td data-label="Amount" class="nowrap">{{ $site->money($job->budget) }}</td>
                                        <td data-label="Status"><x-job-status :job="$job" /></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">None yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        @if ($user->maidProfile)
            <aside class="card card-body">
                <h2 class="card-title mb-2">ID verification</h2>
                <dl class="dl mb-2" style="grid-template-columns: 1fr;">
                    <div><dt>National ID number</dt><dd>{{ $user->maidProfile->national_id_number }}</dd></div>
                </dl>
                <a href="{{ route('admin.users.national-id', $user) }}" target="_blank" rel="noopener">
                    <img src="{{ route('admin.users.national-id', $user) }}" alt="National ID photo" class="thumb thumb--lg" onerror="this.replaceWith(Object.assign(document.createElement('p'), {className: 'small text-muted', textContent: 'ID photo not found on disk.'}))">
                </a>
                <form action="{{ route('admin.users.verify', $user) }}" method="POST" class="stack mt-2">
                    @csrf
                    <div class="inline-actions">
                        <button type="submit" name="status" value="verified" class="btn btn-success btn-sm" @disabled($user->maidProfile->verification_status === 'verified')>✓ Verify</button>
                        <button type="submit" name="status" value="rejected" class="btn btn-danger-outline btn-sm" @disabled($user->maidProfile->verification_status === 'rejected')>Reject</button>
                        <button type="submit" name="status" value="pending" class="btn btn-ghost btn-sm" @disabled($user->maidProfile->verification_status === 'pending')>Reset</button>
                    </div>
                </form>
            </aside>
        @endif
    </div>
@endsection
