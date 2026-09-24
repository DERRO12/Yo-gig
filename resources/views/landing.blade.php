<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YO'gig Maid - Guaranteed Payment & Trusted Gig Cleaning Platform</title>
    
    <!-- External Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <!-- Include Navbar Component -->
    @include('components.navbar')

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-text">
                        <div class="badge-pill">
                            <span>🔒 100% Cashless & Secured Escrow</span>
                        </div>
                        <h1>Reliable Maiding Services. Guaranteed Security.</h1>
                        <p>Connecting verified, professional gig Maids with households, offices, and commercial spaces[cite: 1]. Safe, cashless platform payments ensure total peace of mind for clients and guaranteed earnings for Maids.</p>

                        <div class="hero-cta">
                            <a href="#book" class="btn btn-primary">Book a Maid</a>
                            <a href="#join" class="btn btn-accent">Become a Maid</a>
                        </div>

                        <div class="trust-stats">
                            <div class="stat-item">
                                <h4>0% Cash</h4>
                                <p>Strict Digital Escrow</p>
                            </div>
                            <div class="stat-item">
                                <h4>100% Verified</h4>
                                <p>Vetted & Trained Maids</p>
                            </div>
                            <div class="stat-item">
                                <h4>Guaranteed</h4>
                                <p>Payout Upon Completion</p>
                            </div>
                        </div>
                    </div>

                    <div class="hero-card-preview" style="background: var(--primary); padding: 2.5rem; border-radius: var(--radius-lg); color: white; box-shadow: var(--shadow-md);">
                        <h3 style="color: var(--accent); margin-bottom: 1rem;">Why Choose Cashless Loyalty?</h3>
                        <p style="margin-bottom: 1.5rem; color: #CBD5E1;">No price haggling, no direct cash handling, and full protection for both sides.</p>
                        <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.8rem;">
                            <li>✓ Client funds held safely in Escrow</li>
                            <li>✓ Automatic maid payout upon job sign-off</li>
                            <li>✓ Built-in dispute protection and rating system</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Escrow / How Payment Works Section -->
        <section class="escrow-section">
            <div class="container">
                <div class="escrow-card">
                    <div style="text-align: center; max-width: 700px; margin: 0 auto;">
                        <h2>How Platform Escrow Protects You</h2>
                        <p style="color: #CBD5E1;">To foster maximum loyalty and trust, all transactions are processed entirely through the YO'gig platform. No direct cash payments are permitted.</p>
                    </div>

                    <div class="escrow-grid">
                        <div class="escrow-step">
                            <div class="step-num">1</div>
                            <h3>Client Books & Pays Platform</h3>
                            <p>Client selects a service and deposits funds into the secure platform account.</p>
                        </div>

                        <div class="escrow-step">
                            <div class="step-num">2</div>
                            <h3>Maid Performs Gig</h3>
                            <p>Trained Maid completes the residential, office, or commercial cleaning job.</p>
                        </div>

                        <div class="escrow-step">
                            <div class="step-num">3</div>
                            <h3>Job Approval & Instant Release</h3>
                            <p>Upon client confirmation, the platform releases earnings directly to the Maid's account.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Offered -->
        <section id="services" class="services-section">
            <div class="container">
                <h2 class="section-title">Comprehensive Maiding Solutions</h2>
                <p class="section-subtitle">Flexible gig options tailored for every environment.</p>

                <div class="services-grid">
                    <div class="service-card">
                        <div class="icon-box">🏠</div>
                        <h3>Residential Cleaning</h3>
                        <p>House, apartment, deep clean, and move-in/move-out services tailored to your household.</p>
                    </div>

                    <div class="service-card">
                        <div class="icon-box">🏢</div>
                        <h3>Office Cleaning</h3>
                        <p>Desks, washrooms, reception areas, and scheduled maintenance for businesses.</p>
                    </div>

                    <div class="service-card">
                        <div class="icon-box">🏪</div>
                        <h3>Commercial Spaces</h3>
                        <p>Dedicated cleaning for retail shops, restaurants, and property managers.</p>
                    </div>

                    <div class="service-card">
                        <div class="icon-box">🏫</div>
                        <h3>Institutional Cleaning</h3>
                        <p>Trusted cleaning for schools, non-profits, and community facilities.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Audience Dual Split (Clients vs Maids) -->
        <section class="audience-section">
            <div class="container">
                <div class="dual-grid">
                    <!-- Client Side -->
                    <div class="audience-card client-side" id="book">
                        <h3>Need a Cleaning Gig Done?</h3>
                        <p>Book verified, equipped Maids who deliver high-quality results.</p>
                        <ul class="audience-list">
                            <li>✓ Transparent fixed pricing</li>
                            <li>✓ Safe escrow payment guarantee</li>
                            <li>✓ Verified worker background profiles</li>
                        </ul>
                        <a href="#" class="btn btn-primary" style="width: 100%;">Book a Maid Now</a>
                    </div>

                    <!-- Maid Side -->
                    <div class="audience-card maid-side" id="join">
                        <h3>Want to Earn as a Gig Maid?</h3>
                        <p>Join our platform, get professional training, and access flexible income jobs.</p>
                        <ul class="audience-list">
                            <li>✓ Guaranteed prompt payouts</li>
                            <li>✓ Free customer service & safety skills training</li>
                            <li>✓ Flexible hours & starter kit support</li>
                        </ul>
                        <a href="#" class="btn btn-accent" style="width: 100%;">Apply to Join as a Maid</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Include Footer Component -->
    @include('components.footer')

</body>
</html>