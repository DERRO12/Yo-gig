<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | YO'gig Maid</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .admin-container {
            padding: 3rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            background: #ffffff;
            border-radius: var(--radius-lg, 12px);
            overflow: hidden;
            box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.1));
        }
        .admin-table th, .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #E2E8F0;
        }
        .admin-table th {
            background-color: var(--primary, #0F4C5C);
            color: #ffffff;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .status-badge {
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .status-open { background: #FEF3C7; color: #92400E; }
        .status-matched { background: #D1FAE5; color: #065F46; }
        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }
        .btn-danger {
            background-color: #EF4444;
            color: #ffffff;
            border: none;
            cursor: pointer;
        }
        .alert-success {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

    @include('components.navbar')

    <div class="admin-container">
        <h1 class="section-title">Admin Management Portal</h1>
        <p class="section-subtitle" style="margin-bottom: 2rem;">Manage active jobs, assign maids, and oversee platform gitting.</p>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <h3>All Job Listings</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Title & Location</th>
                    <th>Budget</th>
                    <th>Status</th>
                    <th>Assigned Maid</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td>#{{ $job->id }}</td>
                        <td>{{ $job->client->name }}<br><small>{{ $job->client->phone_number }}</small></td>
                        <td>
                            <strong>{{ $job->title }}</strong><br>
                            <small>{{ $job->location }}</small>
                        </td>
                        <td>UGX {{ number_format($job->budget) }}</td>
                        <td>
                            <span class="status-badge {{ $job->status === 'open' ? 'status-open' : 'status-matched' }}">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                        <td>
                            @if ($job->assigned_maid_id)
                                <strong>{{ $job->assignedMaid->name }}</strong>
                            @else
                                <form action="{{ route('admin.jobs.match', $job->id) }}" method="POST" style="display: flex; gap: 0.5rem;">
                                    @csrf
                                    <select name="maid_id" required style="padding: 0.35rem; border-radius: 6px; border: 1px solid #CBD5E1;">
                                        <option value="">Select Maid</option>
                                        @foreach ($maids as $maid)
                                            <option value="{{ $maid->id }}">{{ $maid->name }} ({{ $maid->phone_number }})</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Match</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('admin.jobs.delete', $job->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this job post?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted, #64748B);">No jobs posted yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('components.footer')

</body>
</html>