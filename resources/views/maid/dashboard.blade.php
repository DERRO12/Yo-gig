<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maid Dashboard | YO'gig Maid</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 2.5rem 1rem; }
        .card-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.75rem;
            border: 1px solid #E2E8F0;
            margin-bottom: 1.5rem;
        }
        .section-heading {
            font-size: 1.15rem;
            margin-bottom: 1rem;
            color: var(--primary, #0F4C5C);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gig-card {
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            background: #FAFAFA;
            transition: border-color 0.2s ease;
        }
        .gig-card:hover { border-color: var(--primary, #0F4C5C); }
        .gig-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }
        .gig-title { margin: 0; font-size: 1.05rem; color: var(--text-dark, #0F172A); }
        .gig-meta {
            font-size: 0.85rem;
            color: var(--text-muted, #64748B);
            margin-bottom: 0.5rem;
        }
        .gig-desc {
            font-size: 0.9rem;
            color: var(--text-dark, #0F172A);
            margin-bottom: 0.75rem;
        }
        .gig-images {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 0.75rem;
        }
        .gig-images img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
            cursor: pointer;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-open { background: #FEF3C7; color: #92400E; }
        .badge-matched { background: #D1FAE5; color: #065F46; }
        .badge-area { background: #DBEAFE; color: #1E40AF; margin-left: 0.5rem; }
        .gig-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }
        .btn-sm { padding: 0.45rem 0.9rem; font-size: 0.85rem; }
        .btn-success { background: #059669; color: #fff; border: none; }
        .btn-success:hover { background: #047857; }
        .btn-warning { background: #F59E0B; color: #fff; border: none; }
        .btn-warning:hover { background: #D97706; }
        .btn-outline {
            background: transparent;
            color: var(--primary, #0F4C5C);
            border: 1px solid var(--primary, #0F4C5C);
        }
        .btn-outline:hover { background: #F1F5F9; }
        .client-details {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-top: 0.75rem;
            font-size: 0.85rem;
        }
        .client-details strong { color: #065F46; }
        .empty-state {
            color: var(--text-muted, #64748B);
            text-align: center;
            padding: 1.5rem 0;
            font-size: 0.9rem;
        }
        .alert-success {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .alert-error {
            background-color: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid #E2E8F0;
        }
        .tab-btn {
            padding: 0.65rem 1rem;
            background: transparent;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-muted, #64748B);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
        }
        .tab-btn.active {
            color: var(--primary, #0F4C5C);
            border-bottom-color: var(--primary, #0F4C5C);
        }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
    </style>
</head>
<body>

    @include('components.navbar')

    <div class="dashboard-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 class="section-title" style="margin-bottom: 0.25rem;">Maid Portal</h1>
                <p class="section-subtitle">Welcome back, {{ Auth::user()->name }}</p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-accent" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Logout</button>
            </form>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        @php
            $me = Auth::user();
            $maidArea = optional($me->maidProfile)->service_area;

            // Gigs in my area
            $areaGigs = collect();
            if ($maidArea) {
                $areaGigs = \App\Models\Job::where('status', 'open')
                    ->where('location', 'like', '%' . $maidArea . '%')
                    ->with('client')
                    ->latest()
                    ->get();
            }

            // Other open gigs (excluding area gigs)
            $areaIds = $areaGigs->pluck('id')->toArray();
            $otherGigs = \App\Models\Job::where('status', 'open')
                ->whereNotIn('id', $areaIds)
                ->with('client')
                ->latest()
                ->get();

            // My assigned gigs
            $myJobs = \App\Models\Job::where('maid_id', $me->id)
                ->with('client')
                ->latest()
                ->get();

            $totalOpen = $areaGigs->count() + $otherGigs->count();
            $totalAssigned = $myJobs->count();
        @endphp

        {{-- Quick stats --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="card-box" style="margin-bottom: 0;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Open Gigs</div>
                <div style="font-size: 1.75rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">{{ $totalOpen }}</div>
            </div>
            <div class="card-box" style="margin-bottom: 0;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Your Assigned Gigs</div>
                <div style="font-size: 1.75rem; font-weight: 800; color: #065F46; margin-top: 0.25rem;">{{ $totalAssigned }}</div>
            </div>
            <div class="card-box" style="margin-bottom: 0;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Expected Earnings</div>
                <div style="font-size: 1.75rem; font-weight: 800; color: #92400E; margin-top: 0.25rem;">
                    UGX {{ number_format($myJobs->sum('budget')) }}
                </div>
            </div>
            <div class="card-box" style="margin-bottom: 0;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Your Area</div>
                <div style="font-size: 1rem; font-weight: 700; color: var(--text-dark); margin-top: 0.5rem;">
                    {{ $maidArea ?: 'Not set' }}
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('available', this)">Available Gigs ({{ $totalOpen }})</button>
            <button class="tab-btn" onclick="switchTab('assigned', this)">Your Assigned Gigs ({{ $totalAssigned }})</button>
        </div>

        {{-- TAB: AVAILABLE GIGS --}}
        <div class="tab-panel active" id="tab-available">
            @if($maidArea && $areaGigs->count() > 0)
                <div class="card-box">
                    <div class="section-heading">
                        <span>📍 Gigs in your area ({{ $maidArea }})</span>
                        <span class="badge badge-area">{{ $areaGigs->count() }}</span>
                    </div>

                    @foreach($areaGigs as $job)
                        <div class="gig-card">
                            <div class="gig-header">
                                <h4 class="gig-title">{{ $job->title }}</h4>
                                <span class="badge badge-open">Open</span>
                            </div>
                            <div class="gig-meta">
                                📍 {{ $job->location }}
                                @if($job->budget) &nbsp;|&nbsp; 💰 UGX {{ number_format($job->budget) }} @endif
                                @if($job->scheduled_date)
                                    &nbsp;|&nbsp; 📅 {{ \Carbon\Carbon::parse($job->scheduled_date)->format('D, M j') }}
                                    @if($job->scheduled_time)
                                        @ {{ \Carbon\Carbon::parse($job->scheduled_time)->format('g:i A') }}
                                    @endif
                                @endif
                            </div>
                            @if($job->description)
                                <p class="gig-desc">{{ Str::limit($job->description, 200) }}</p>
                            @endif
                            @if($job->images && count($job->images) > 0)
                                <div class="gig-images">
                                    @foreach($job->images as $img)
                                        <a href="{{ asset('storage/' . $img) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $img) }}" alt="Job photo">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <div class="gig-actions">
                                <a href="{{ route('maid.gigs.show', $job) }}" class="btn btn-outline btn-sm">View Details</a>
                                <form action="{{ route('maid.gigs.claim', $job) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Claim this gig? The client will be notified.');">
                                        Claim This Gig
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Other areas --}}
            <div class="card-box">
                <div class="section-heading">
                    <span>🌍 Gigs in other areas</span>
                    <span class="badge badge-open">{{ $otherGigs->count() }}</span>
                </div>

                @forelse($otherGigs as $job)
                    <div class="gig-card">
                        <div class="gig-header">
                            <h4 class="gig-title">{{ $job->title }}</h4>
                            <span class="badge badge-open">Open</span>
                        </div>
                        <div class="gig-meta">
                            📍 {{ $job->location }}
                            @if($job->budget) &nbsp;|&nbsp; 💰 UGX {{ number_format($job->budget) }} @endif
                            @if($job->scheduled_date)
                                &nbsp;|&nbsp; 📅 {{ \Carbon\Carbon::parse($job->scheduled_date)->format('D, M j') }}
                            @endif
                        </div>
                        @if($job->description)
                            <p class="gig-desc">{{ Str::limit($job->description, 200) }}</p>
                        @endif
                        @if($job->images && count($job->images) > 0)
                            <div class="gig-images">
                                @foreach($job->images as $img)
                                    <a href="{{ asset('storage/' . $img) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $img) }}" alt="Job photo">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        <div class="gig-actions">
                            <a href="{{ route('maid.gigs.show', $job) }}" class="btn btn-outline btn-sm">View Details</a>
                            <form action="{{ route('maid.gigs.claim', $job) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Claim this gig?');">
                                    Claim This Gig
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    @if(!$maidArea || $areaGigs->count() === 0)
                        <div class="empty-state">No open gigs available right now. Check back soon!</div>
                    @endif
                @endforelse
            </div>
        </div>

        {{-- TAB: ASSIGNED GIGS --}}
        <div class="tab-panel" id="tab-assigned">
            <div class="card-box">
                <div class="section-heading">
                    <span>✅ Your Assigned Gigs</span>
                    <span class="badge badge-matched">{{ $myJobs->count() }}</span>
                </div>

                @forelse($myJobs as $job)
                    <div class="gig-card">
                        <div class="gig-header">
                            <h4 class="gig-title">{{ $job->title }}</h4>
                            <span class="badge badge-matched">{{ ucfirst($job->status) }}</span>
                        </div>
                        <div class="gig-meta">
                            📍 {{ $job->location }}
                            @if($job->budget) &nbsp;|&nbsp; 💰 UGX {{ number_format($job->budget) }} @endif
                            @if($job->scheduled_date)
                                &nbsp;|&nbsp; 📅 {{ \Carbon\Carbon::parse($job->scheduled_date)->format('D, M j, Y') }}
                                @if($job->scheduled_time)
                                    @ {{ \Carbon\Carbon::parse($job->scheduled_time)->format('g:i A') }}
                                @endif
                            @endif
                        </div>
                        @if($job->description)
                            <p class="gig-desc">{{ $job->description }}</p>
                        @endif
                        @if($job->images && count($job->images) > 0)
                            <div class="gig-images">
                                @foreach($job->images as $img)
                                    <a href="{{ asset('storage/' . $img) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $img) }}" alt="Job photo">
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Client contact details (visible after claim) --}}
                        @if($job->client)
                            <div class="client-details">
                                <div><strong>Client:</strong> {{ $job->client->name }}</div>
                                <div>
                                    <strong>Phone:</strong>
                                    <a href="tel:{{ $job->client->phone_number }}" style="color: #065F46; font-weight: 600;">
                                        {{ $job->client->phone_number }}
                                    </a>
                                </div>
                                <div><strong>Email:</strong> {{ $job->client->email }}</div>
                            </div>
                        @endif

                        <div class="gig-actions">
                            @if(!in_array($job->status, ['in_progress', 'completed']))
                                <form action="{{ route('maid.gigs.release', $job) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Release this gig? It will become available for other maids.');">
                                        Release Gig
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        You haven't claimed any gigs yet. Head to <strong>Available Gigs</strong> and pick one!
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @include('components.footer')

    <script>
        function switchTab(name, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById('tab-' + name).classList.add('active');
        }
    </script>

</body>
</html>