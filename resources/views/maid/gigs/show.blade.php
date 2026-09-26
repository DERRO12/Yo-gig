<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $job->title }} | YO'gig Maid</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .detail-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2.5rem 1rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--primary, #0F4C5C);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .back-link:hover { text-decoration: underline; }
        .detail-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .detail-title {
            margin: 0;
            font-size: 1.6rem;
            color: var(--text-dark, #0F172A);
        }
        .badge {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge-open { background: #FEF3C7; color: #92400E; }
        .badge-matched { background: #D1FAE5; color: #065F46; }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1.25rem;
            background: #F8FAFC;
            border-radius: 10px;
        }
        .meta-item .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted, #64748B);
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        .meta-item .value {
            font-size: 1rem;
            color: var(--text-dark, #0F172A);
            font-weight: 600;
        }
        .section-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--text-muted, #64748B);
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .description-text {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--text-dark, #0F172A);
            white-space: pre-wrap;
        }
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .image-gallery a {
            display: block;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #E2E8F0;
            transition: transform 0.2s ease;
        }
        .image-gallery a:hover { transform: scale(1.03); }
        .image-gallery img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            display: block;
        }
        .action-bar {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #E2E8F0;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: var(--primary, #0F4C5C);
            color: #fff;
        }
        .btn-primary:hover { background: #0d3d4a; }
        .btn-success {
            background: #059669;
            color: #fff;
        }
        .btn-success:hover { background: #047857; }
        .btn-warning {
            background: #F59E0B;
            color: #fff;
        }
        .btn-warning:hover { background: #D97706; }
        .btn-outline {
            background: transparent;
            color: var(--primary, #0F4C5C);
            border: 1px solid var(--primary, #0F4C5C);
        }
        .btn-outline:hover { background: #F1F5F9; }
        .client-contact {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-radius: 10px;
            padding: 1.25rem;
            margin-top: 1.5rem;
        }
        .client-contact h3 {
            margin: 0 0 0.75rem;
            color: #065F46;
            font-size: 1rem;
        }
        .client-contact .row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            font-size: 0.9rem;
        }
        .client-contact .row strong { color: #065F46; }
        .client-contact a {
            color: #065F46;
            font-weight: 600;
            text-decoration: none;
        }
        .client-contact a:hover { text-decoration: underline; }
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
    </style>
</head>
<body>

    @include('components.navbar')

    <div class="detail-container">

        <a href="{{ route('maid.dashboard') }}" class="back-link">← Back to Dashboard</a>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        <div class="detail-card">
            <div class="detail-header">
                <h1 class="detail-title">{{ $job->title }}</h1>
                <span class="badge badge-{{ $job->status === 'open' ? 'open' : 'matched' }}">
                    {{ ucfirst($job->status) }}
                </span>
            </div>

            <div class="meta-grid">
                <div class="meta-item">
                    <div class="label">📍 Location</div>
                    <div class="value">{{ $job->location }}</div>
                </div>

                @if($job->budget)
                    <div class="meta-item">
                        <div class="label">💰 Budget</div>
                        <div class="value">UGX {{ number_format($job->budget) }}</div>
                    </div>
                @endif

                @if($job->scheduled_date)
                    <div class="meta-item">
                        <div class="label">📅 Scheduled</div>
                        <div class="value">
                            {{ \Carbon\Carbon::parse($job->scheduled_date)->format('D, M j, Y') }}
                            @if($job->scheduled_time)
                                <br>at {{ \Carbon\Carbon::parse($job->scheduled_time)->format('g:i A') }}
                            @endif
                        </div>
                    </div>
                @endif

                <div class="meta-item">
                    <div class="label">🕒 Posted</div>
                    <div class="value">{{ $job->created_at->diffForHumans() }}</div>
                </div>
            </div>

            @if($job->description)
                <div style="margin-top: 1.5rem;">
                    <div class="section-label">Job Description</div>
                    <div class="description-text">{{ $job->description }}</div>
                </div>
            @endif

            @if($job->images && count($job->images) > 0)
                <div style="margin-top: 1.5rem;">
                    <div class="section-label">📸 Photos ({{ count($job->images) }})</div>
                    <div class="image-gallery">
                        @foreach($job->images as $img)
                            <a href="{{ asset('storage/' . $img) }}" target="_blank">
                                <img src="{{ asset('storage/' . $img) }}" alt="Job photo">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Client contact details: only visible if this maid claimed the gig --}}
            @if($job->maid_id === Auth::id() && $job->client)
                <div class="client-contact">
                    <h3>✅ Client Contact Details</h3>
                    <div class="row">
                        <div>
                            <strong>Name:</strong> {{ $job->client->name }}
                        </div>
                        <div>
                            <strong>Phone:</strong>
                            <a href="tel:{{ $job->client->phone_number }}">
                                📞 {{ $job->client->phone_number }}
                            </a>
                        </div>
                        <div>
                            <strong>Email:</strong>
                            <a href="mailto:{{ $job->client->email }}">
                                {{ $job->client->email }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="action-bar">
                @if($job->status === 'open')
                    <form action="{{ route('maid.gigs.claim', $job) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Claim this gig? The client will be able to see your profile.');">
                            ✓ Claim This Gig
                        </button>
                    </form>
                @elseif($job->maid_id === Auth::id())
                    @if(!in_array($job->status, ['in_progress', 'completed']))
                        <form action="{{ route('maid.gigs.release', $job) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Release this gig? It will be available for other maids.');">
                                ↺ Release This Gig
                            </button>
                        </form>
                    @endif
                @endif

                <a href="{{ route('maid.dashboard') }}" class="btn btn-outline">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

    </div>

    @include('components.footer')

</body>
</html>