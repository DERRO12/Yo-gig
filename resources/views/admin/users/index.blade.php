@extends('layouts.admin')

@section('title', 'Users & maids')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Users & maids</h1>
            <p>Verify maid IDs, review accounts and suspend misuse.</p>
        </div>
    </div>

    <form method="GET" class="filters">
        <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="Search name, email or phone">
        <select name="role" class="input">
            <option value="">All roles</option>
            @foreach (['client' => 'Clients', 'maid' => 'Maids', 'admin' => 'Admins'] as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="verification" class="input">
            <option value="">Any verification</option>
            @foreach (['pending', 'verified', 'rejected'] as $value)
                <option value="{{ $value }}" @selected(request('verification') === $value)>{{ ucfirst($value) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        @if (request()->hasAny(['q', 'role', 'verification']))
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        @endif
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table table--stack">
                <thead><tr><th>User</th><th>Role</th><th>Phone</th><th>Activity</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td data-label="User">
                                <a href="{{ route('admin.users.show', $user) }}" class="fw-bold">{{ $user->name }}</a>
                                <div class="small text-muted">{{ $user->email }}</div>
                            </td>
                            <td data-label="Role"><span class="badge badge--teal">{{ ucfirst($user->role) }}</span></td>
                            <td data-label="Phone" class="nowrap">{{ $user->phone_number }}</td>
                            <td data-label="Activity" class="small">
                                @if ($user->isMaid()) {{ $user->jobs_as_maid_count }} jobs @elseif ($user->isClient()) {{ $user->jobs_as_client_count }} bookings @else — @endif
                            </td>
                            <td data-label="Status">
                                @if ($user->maidProfile)
                                    <x-record-status :status="$user->maidProfile->verification_status" />
                                @endif
                                @unless ($user->is_active)
                                    <span class="badge badge--red">Suspended</span>
                                @endunless
                            </td>
                            <td data-label=""><a href="{{ route('admin.users.show', $user) }}" class="btn btn-ghost btn-sm">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}
@endsection
