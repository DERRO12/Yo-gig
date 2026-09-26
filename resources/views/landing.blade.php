<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YO'gig Maid | On-Demand Professional Cleaning & Secure Escrow Payments</title>
    
    <!-- Meta Descriptions for SEO -->
    <meta name="description" content="Book vetted, background-checked cleaning professionals for home and office spaces. Cashless escrow payments guarantee 100% satisfaction and zero payment risk.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- External Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <!-- Landing-only styles -->
    <style>
        .fresh-gigs-section {
            background: #F8FAFC;
            padding: 4rem 0;
        }
        .fresh-gigs-header {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .fresh-gigs-header h2 {
            font-size: 2rem;
            color: var(--text-dark, #0F172A);
            margin: 0;
        }
        .fresh-gigs-header p {
            color: var(--text-muted, #64748B);
            margin: 0.4rem 0 0;
            font-size: 0.95rem;
        }
        .fresh-gigs-count {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #FEF3C7;
            color: #92400E;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .fresh-gigs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
        }
        .gig-preview-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .gig-preview-card:hover {
            border-color: var(--primary, #0F4C5C);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 76, 92, 0.08);
        }
        .gig-preview-title {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-dark, #0F172A);
            font-weight: 700;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .gig-preview-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted, #64748B);
        }
        .gig-preview-meta-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .gig-preview-meta-row .icon {
            width: 16px;
            text-align: center;
            flex-shrink: 0;
        }
        .gig-preview-budget {
            display: inline-block;
            background: #D1FAE5;
            color: #065F46;
            padding: 0.4rem 0.85rem;
            border-radius: 8px;
            font-weight: 800;
            font-size: 0.95rem;
            align-self: flex-start;
        }
        .gig-preview-footer {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
            padding-top: 0.85rem;
            border-top: 1px solid #E2E8F0;
        }
        .gig-preview-footer .btn {
            flex: 1;
            text-align: center;
            padding: 0.6rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .gig-preview-footer .btn-primary {
            background: var(--primary, #0F4C5C);
            color: #fff;
        }
        .gig-preview-footer .btn-primary:hover {
            background: #0d3d4a;
        }
        .gig-preview-footer .btn-outline {
            background: transparent;
            color: var(--primary, #0F4C5C);
            border: 1px solid var(--primary, #0F4C5C);
        }
        .gig-preview-footer .btn-outline:hover {
            background: #F1F5F9;
        }
        .gig-preview-posted {
            font-size: 0.75rem;
            color: var(--text-muted, #94A3B8);
            text-align: right;
        }
        .fresh-gigs-empty {
            background: #fff;
            border: 1px dashed #CBD5E1;
            border-radius: 12px;
            padding: 3rem 1.5rem;
            text-align: center;
            color: var(--text-muted, #64748B);
        }
        .fresh-gigs-empty h3 {
            margin: 0 0 0.5rem;
            color: var(--text-dark, #0F172A);
            font-size: 1.1rem;
        }
        @media (max-width: 640px) {
            .fresh-gigs-header h2 { font-size: 1.5rem; }
            .fresh-gigs-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Header & Navigation -->
    @include('components.navbar')

    <main>
        <!-- Hero Slider Section -->
        <section 
            class="hero hero-bg-overlay hero-slide-1-bg" 
            id="hero-section"
            style="--hero-img-1: url('{{ asset('images/cashless.jpg') }}'); --hero-img-2: url('{{ asset('images/office.jpg') }}');"
        >
            <div class="container hero-slider-container">
                
                <!-- Slide 1: For Clients -->
                <article class="hero-grid hero-slide active" id="slide-1" aria-label="Services for Clients">
                    <div class="hero-text">
                        <div class="badge-pill">
                            <span>✨ Instant Booking & Vetted Quality</span>
                        </div>
                        <h1>Spotless Spaces, Protected Payments.</h1>
                        <p>Book background-checked gig Maids for home and office cleaning with complete peace of mind. Your money stays locked in secure escrow until you approve the finished work.</p>

                        <div class="hero-cta">
                            <a href="#book" class="btn btn-primary">Book a Maid Today</a>
                            <a href="#fresh-gigs" class="btn btn-accent">See Open Gigs</a>
                        </div>

                        <div class="trust-stats">
                            <div class="stat-item">
                                <h4>0% Cash Risk</h4>
                                <p>100% Escrow Protection</p>
                            </div>
                            <div class="stat-item">
                                <h4>100% Vetted</h4>
                                <p>Identity & Background Checked</p>
                            </div>
                            <div class="stat-item">
                                <h4>4.9 / 5.0</h4>
                                <p>Verified Client Rating</p>
                            </div>
                        </div>
                    </div>

                    <div class="hero-card-preview client-preview-card">
                        <h3 class="card-title-accent">Client Peace of Mind</h3>
                        <p class="card-description">Cash-free, dispute-free cleaning. Your deposit is held safely by our gateway and released only upon your final inspection.</p>
                        <ul class="feature-check-list">
                            <li><span class="check-icon">✓</span> Rigorously screened & trained cleaners</li>
                            <li><span class="check-icon">✓</span> Transparent, upfront fixed pricing</li>
                            <li><span class="check-icon">✓</span> Built-in platform dispute resolution</li>
                        </ul>
                    </div>
                </article>

                <!-- Slide 2: For Maids / Gig Workers -->
                <article class="hero-grid hero-slide" id="slide-2" aria-label="Opportunities for Gig Maids" style="display: none;">
                    <div class="hero-text">
                        <div class="badge-pill">
                            <span>💼 Work & Earn On Your Terms</span>
                        </div>
                        <h1>Turn Cleaning Skills Into Guaranteed Income.</h1>
                        <p>Join our professional gig network. Enjoy free skill certification, safety equipment, and instant direct-to-wallet payouts for every job well done.</p>

                        <div class="hero-cta">
                            <a href="#join" class="btn btn-primary">Apply as a Maid</a>
                            <a href="{{ route('about') }}" class="btn btn-accent">Learn How It Works</a>
                        </div>

                        <div class="trust-stats">
                            <div class="stat-item">
                                <h4>100% Payout</h4>
                                <p>Guaranteed Upon Sign-off</p>
                            </div>
                            <div class="stat-item">
                                <h4>Starter Kit</h4>
                                <p>Free Equipment & Gear</p>
                            </div>
                            <div class="stat-item">
                                <h4>Flexible</h4>
                                <p>Set Your Own Schedule</p>
                            </div>
                        </div>
                    </div>

                    <div class="hero-card-preview maid-preview-card">
                        <h3 class="card-title-accent">Gig Maid Advantages</h3>
                        <p class="card-description">Never worry about unpaid labor. The platform secures client funds in escrow before you start any job.</p>
                        <ul class="feature-check-list">
                            <li><span class="check-icon">✓</span> Immediate digital funds release</li>
                            <li><span class="check-icon">✓</span> Free health, safety, and PPE kit</li>
                            <li><span class="check-icon">✓</span> Financial literacy & skill advancement</li>
                        </ul>
                    </div>
                </article>

                <!-- Slider Control Dots -->
                <div class="slider-dots" role="tablist" aria-label="Hero Slider Controls">
                    <button class="dot active" onclick="setSlide(0)" aria-label="Slide 1"></button>
                    <button class="dot" onclick="setSlide(1)" aria-label="Slide 2"></button>
                </div>

            </div>
        </section>

        <!-- Payment Escrow Step Breakdown -->
        <section class="escrow-section" id="escrow-how-it-works">
            <div class="container">
                <div class="escrow-card escrow-bg-overlay" style="--escrow-img: url('{{ asset('images/cashless.jpg') }}');">
                    <h2>How Escrow Protects Both Sides</h2>
                    <p>Transparent digital payments built for safety and trust.</p>
                    <div class="escrow-grid">
                        <div class="escrow-step">
                            <div class="step-num">1</div>
                            <h3>Client Deposits Funds</h3>
                            <p>Funds are secured in escrow prior to service starting.</p>
                        </div>
                        <div class="escrow-step">
                            <div class="step-num">2</div>
                            <h3>Job Completed</h3>
                            <p>Maid completes cleaning with verified quality checks.</p>
                        </div>
                        <div class="escrow-step">
                            <div class="step-num">3</div>
                            <h3>Instant Payout</h3>
                            <p>Client approves and funds transfer directly to the maid's wallet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================= -->
        <!-- FRESH GIGS SECTION — Latest open jobs on the platform -->
        <!-- ================================================= -->
        @php
            $freshGigs = \App\Models\Job::where('status', 'open')
                ->with('client')
                ->latest()
                ->take(6)
                ->get();

            $totalOpenGigs = \App\Models\Job::where('status', 'open')->count();
        @endphp

        <section class="fresh-gigs-section" id="fresh-gigs">
            <div class="container">
                <div class="fresh-gigs-header">
                    <div>
                        <h2>Fresh Gigs Waiting</h2>
                        <p>Latest cleaning opportunities posted by clients on the platform.</p>
                    </div>
                    @if($totalOpenGigs > 0)
                        <span class="fresh-gigs-count">
                            🔥 {{ $totalOpenGigs }} open {{ $totalOpenGigs === 1 ? 'gig' : 'gigs' }}
                        </span>
                    @endif
                </div>

                @if($freshGigs->isEmpty())
                    <div class="fresh-gigs-empty">
                        <h3>No open gigs right now</h3>
                        <p>Check back soon — new cleaning gigs are posted daily.</p>
                        <a href="{{ route('register') }}" class="btn btn-primary" style="margin-top: 1rem; display: inline-block; padding: 0.7rem 1.5rem;">
                            Register as a Maid
                        </a>
                    </div>
                @else
                    <div class="fresh-gigs-grid">
                        @foreach($freshGigs as $gig)
                            <article class="gig-preview-card">
                                <h3 class="gig-preview-title">{{ $gig->title }}</h3>

                                @if($gig->budget)
                                    <span class="gig-preview-budget">
                                        UGX {{ number_format($gig->budget) }}
                                    </span>
                                @endif

                                <div class="gig-preview-meta">
                                    <div class="gig-preview-meta-row">
                                        <span class="icon">📍</span>
                                        <span>{{ $gig->location }}</span>
                                    </div>

                                    @if($gig->scheduled_date)
                                        <div class="gig-preview-meta-row">
                                            <span class="icon">📅</span>
                                            <span>
                                                {{ \Carbon\Carbon::parse($gig->scheduled_date)->format('D, M j, Y') }}
                                                @if($gig->scheduled_time)
                                                    · {{ \Carbon\Carbon::parse($gig->scheduled_time)->format('g:i A') }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <div class="gig-preview-posted">
                                    Posted {{ $gig->created_at->diffForHumans() }}
                                </div>

                                <div class="gig-preview-footer">
                                    @auth
                                        @if(auth()->user()->role === 'maid')
                                            <a href="{{ route('maid.gigs.show', $gig) }}" class="btn btn-primary">
                                                View Details
                                            </a>
                                        @else
                                            <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.6; text-align: center;">
                                                Maids only
                                            </span>
                                        @endif
                                    @else
                                        <a href="{{ route('register') }}" class="btn btn-primary">
                                            Join to Claim
                                        </a>
                                    @endauth
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if($totalOpenGigs > 6)
                        <div style="text-align: center; margin-top: 2rem;">
                            <a href="{{ route('register') }}" class="btn btn-accent" style="display: inline-block; padding: 0.85rem 2rem;">
                                See all {{ $totalOpenGigs }} open gigs →
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </section>

    </main>

    @include('components.footer')

    <!-- Unified Hero Slider Controller Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let currentSlide = 0;
            const totalSlides = 2;
            const slideIntervalTime = 5000;
            let slideTimer = null;

            const heroSection = document.getElementById('hero-section');
            const slides = [
                document.getElementById('slide-1'),
                document.getElementById('slide-2')
            ];
            const dots = document.querySelectorAll('.slider-dots .dot');

            function goToSlide(index) {
                currentSlide = index;

                if (heroSection) {
                    if (currentSlide === 0) {
                        heroSection.classList.remove('hero-slide-2-bg');
                        heroSection.classList.add('hero-slide-1-bg');
                    } else {
                        heroSection.classList.remove('hero-slide-1-bg');
                        heroSection.classList.add('hero-slide-2-bg');
                    }
                }

                slides.forEach((slide, idx) => {
                    if (slide) {
                        if (idx === currentSlide) {
                            slide.style.display = 'grid';
                            slide.classList.add('active');
                        } else {
                            slide.style.display = 'none';
                            slide.classList.remove('active');
                        }
                    }
                });

                dots.forEach((dot, idx) => {
                    dot.classList.toggle('active', idx === currentSlide);
                });
            }

            function nextSlide() {
                const nextIndex = (currentSlide + 1) % totalSlides;
                goToSlide(nextIndex);
            }

            function startAutoSlide() {
                if (!slideTimer) {
                    slideTimer = setInterval(nextSlide, slideIntervalTime);
                }
            }

            function pauseAutoSlide() {
                if (slideTimer) {
                    clearInterval(slideTimer);
                    slideTimer = null;
                }
            }

            window.setSlide = function(index) {
                pauseAutoSlide();
                goToSlide(index);
                startAutoSlide();
            };

            if (heroSection) {
                heroSection.addEventListener('mouseenter', pauseAutoSlide);
                heroSection.addEventListener('mouseleave', startAutoSlide);
            }

            goToSlide(0);
            startAutoSlide();
        });
    </script>
</body>
</html>