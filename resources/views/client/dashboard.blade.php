<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard | YO'gig Maid</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1rem;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            align-items: start;
        }
        .card-box {
            background: #ffffff;
            border-radius: var(--radius-lg, 12px);
            padding: 1.75rem;
            border: 1px solid #E2E8F0;
            box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.1));
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-dark, #0F172A);
        }
        .form-control {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary, #0F4C5C);
        }
        .form-hint {
            display: block;
            margin-top: 0.3rem;
            font-size: 0.78rem;
            color: var(--text-muted, #64748B);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
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
        .alert-error ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        .job-item {
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            background: #FAFAFA;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-open { background: #FEF3C7; color: #92400E; }
        .badge-matched { background: #D1FAE5; color: #065F46; }
        .job-image-grid {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }
        .job-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
        }
        .image-preview {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .image-preview img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
        }
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    @include('components.navbar')

    <div class="dashboard-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 class="section-title" style="margin-bottom: 0.25rem;">Client Dashboard</h1>
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

        @if ($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="dashboard-grid">
            <!-- Left Column: Post a New Job -->
            <div class="card-box">
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem; color: var(--primary, #0F4C5C);">Post a New Job</h3>
                <form action="{{ route('jobs.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="title">Job Title</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Deep House Cleaning" value="{{ old('title') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" class="form-control" placeholder="e.g. Ntinda, Kampala" value="{{ old('location') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="budget">Budget (UGX)</label>
                        <input type="number" name="budget" id="budget" class="form-control" placeholder="e.g. 50000" min="0" step="500" value="{{ old('budget') }}" required>
                    </div>

                    <!-- Date + Time side by side -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="scheduled_date">Preferred Date</label>
                            <input type="date" 
                                   name="scheduled_date" 
                                   id="scheduled_date" 
                                   class="form-control" 
                                   value="{{ old('scheduled_date', date('Y-m-d')) }}" 
                                   min="{{ date('Y-m-d') }}"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="scheduled_time">Preferred Time</label>
                            <input type="time" 
                                   name="scheduled_time" 
                                   id="scheduled_time" 
                                   class="form-control" 
                                   value="{{ old('scheduled_time', '09:00') }}" 
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Job Description & Details</label>
                        <textarea name="description" id="description" rows="4" class="form-control" placeholder="Specify tasks, surface cleaning, time preferences..." required>{{ old('description') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="images">Photos of the Space (optional, max 5)</label>
                        <input type="file" 
                               name="images[]" 
                               id="images" 
                               class="form-control" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               multiple>
                        <small class="form-hint">Upload photos so maids can review before claiming the gig. Max 5 images, 5 MB each.</small>
                        <div id="image-preview" class="image-preview"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">Post Gig</button>
                </form>
            </div>

            <!-- Right Column: My Job History & Matched Maids -->
            <div class="card-box">
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem; color: var(--primary, #0F4C5C);">Your Posted Jobs</h3>
                
                @php
                    $myJobs = \App\Models\Job::where('client_id', Auth::id())->with('assignedMaid')->latest()->get();
                @endphp

                @forelse ($myJobs as $job)
                    <div class="job-item">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <h4 style="margin: 0; font-size: 1rem; color: var(--text-dark, #0F172A);">{{ $job->title }}</h4>
                            <span class="badge {{ $job->status === 'open' ? 'badge-open' : 'badge-matched' }}">
                                {{ ucfirst($job->status) }}
                            </span>
                        </div>

                        <p style="font-size: 0.875rem; color: var(--text-muted, #64748B); margin-bottom: 0.5rem;">
                            📍 {{ $job->location }} &nbsp;|&nbsp; 💰 UGX {{ number_format($job->budget) }}
                        </p>

                        @if($job->scheduled_date)
                            <p style="font-size: 0.85rem; color: var(--text-muted, #64748B); margin-bottom: 0.5rem;">
                                📅 {{ \Carbon\Carbon::parse($job->scheduled_date)->format('D, M j, Y') }}
                                @if($job->scheduled_time)
                                    &nbsp;|&nbsp; 🕐 {{ \Carbon\Carbon::parse($job->scheduled_time)->format('g:i A') }}
                                @endif
                            </p>
                        @endif

                        <p style="font-size: 0.875rem; color: var(--text-dark, #0F172A); margin-bottom: 0.75rem;">
                            {{ $job->description }}
                        </p>

                        {{-- Job images --}}
                        @if($job->images && count($job->images) > 0)
                            <div class="job-image-grid">
                                @foreach($job->images as $image)
                                    <a href="{{ asset('storage/' . $image) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $image) }}" 
                                             alt="Job photo" 
                                             class="job-image">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        
                        <div style="border-top: 1px solid #E2E8F0; padding-top: 0.5rem; margin-top: 0.75rem; font-size: 0.85rem;">
                            @if ($job->assignedMaid)
                                <span style="color: #065F46; font-weight: 700;">Matched Maid:</span> 
                                {{ $job->assignedMaid->name }} 
                                &nbsp;·&nbsp; 
                                <a href="tel:{{ $job->assignedMaid->phone_number }}" style="color: var(--primary); font-weight: 600;">
                                    📞 {{ $job->assignedMaid->phone_number }}
                                </a>
                            @else
                                <span style="color: #92400E;">Waiting for a maid to claim this gig or admin to match one...</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color: var(--text-muted, #64748B); text-align: center; padding: 2rem 0;">You haven't posted any cleaning jobs yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    @include('components.footer')

    <!-- Image preview script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('images');
            const preview = document.getElementById('image-preview');

            if (fileInput && preview) {
                fileInput.addEventListener('change', function (e) {
                    preview.innerHTML = '';
                    const files = Array.from(e.target.files).slice(0, 5);

                    files.forEach(file => {
                        const reader = new FileReader();
                        reader.onload = (ev) => {
                            const img = document.createElement('img');
                            img.src = ev.target.result;
                            preview.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    });
                });
            }
        });
    </script>

</body>
</html>