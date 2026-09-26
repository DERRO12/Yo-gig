<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | YO'gig Maid - Empowering Livelihoods Through Professional Cleaning</title>
    
    <!-- Meta Descriptions for SEO -->
    <meta name="description" content="YO'gig Maid addresses unemployment and poverty by building a professional, safe, and cashless gig-cleaning network connecting trained Maids with clients.">
    
    <!-- External Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <!-- Header & Navigation -->
    @include('components.navbar')

    <main>
        <!-- About Hero Header with training.jpg Overlay -->
        <section 
            class="about-hero hero-bg-overlay" 
            style="--hero-bg: url('{{ asset('images/training.jpg') }}');"
        >
            <div class="container text-center">
                <div class="badge-pill badge-pill-center badge-pill-light">
                    <span>Our Mission & Ecosystem</span>
                </div>
                <h1 class="hero-title">Transforming Gig Cleaning Into Sustainable Livelihoods</h1>
                <p class="hero-subtitle">
                    YO'gig Maid addresses unemployment and poverty by building a professional, safe, and cashless gig-cleaning network connecting trained Maids with households, offices, and commercial spaces.
                </p>
            </div>
        </section>

        <!-- Mission & Core Promise Section -->
        <section class="services-section bg-white">
            <div class="container">
                <div class="dual-grid">
                    <article class="service-card card-padded">
                        <span class="card-number">Mission</span>
                        <h3>Our Goal</h3>
                        <p>To contribute directly to poverty reduction by generating flexible, dignified, and sustainable self-employment opportunities through high-standard maiding services.</p>
                    </article>

                    <article class="service-card card-padded">
                        <span class="card-number">Guarantee</span>
                        <h3>Our Core Promise</h3>
                        <p>A 100% cashless, escrow-backed ecosystem. Clients get verified quality, and Maids receive guaranteed, prompt digital payouts upon job sign-off.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Pillars of Empowerment -->
        <section class="services-section">
            <div class="container">
                <div class="section-header-center">
                    <h2 class="section-title">How We Support Our Gig Workers</h2>
                    <p class="section-subtitle">We do not just connect jobs—we equip workers with skills for long-term financial resilience.</p>
                </div>

                <div class="services-grid">
                    <article class="service-card">
                        <span class="card-number">01</span>
                        <h3>Professional Skill Training</h3>
                        <p>Comprehensive instruction on sanitation, surface care, deep cleaning, and specialized equipment use.</p>
                    </article>

                    <article class="service-card">
                        <span class="card-number">02</span>
                        <h3>Health & Safety Protocols</h3>
                        <p>Strict occupational health standards, chemical safety education, and personal protective equipment (PPE) guidelines.</p>
                    </article>

                    <article class="service-card">
                        <span class="card-number">03</span>
                        <h3>Financial Literacy</h3>
                        <p>Training in budgeting, savings, record-keeping, and income management to build lasting economic security.</p>
                    </article>

                    <article class="service-card">
                        <span class="card-number">04</span>
                        <h3>Customer Service Excellence</h3>
                        <p>Etiquette, workplace safety, communication, and professionalism to maintain high customer satisfaction.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Target Economic Impact Section -->
        <section class="escrow-section escrow-bg-overlay" style="--escrow-img: url('{{ asset('images/training.jpg') }}');">
            <div class="container">
                <div class="escrow-card text-center">
                    <h2 class="impact-title">Targeted Economic Impact</h2>
                    <p class="impact-description">
                        Our program focuses on uplifting unemployed youth, underemployed adults, and low-income households by providing starter equipment, skills verification, and marketplace access.
                    </p>

                    <div class="escrow-grid">
                        <div class="escrow-step">
                            <h3 class="stat-highlight">200+</h3>
                            <p>Target beneficiaries trained and onboarded into active gig work.</p>
                        </div>
                        <div class="escrow-step">
                            <h3 class="stat-highlight">100%</h3>
                            <p>Cashless digital payment security for zero payment disputes.</p>
                        </div>
                        <div class="escrow-step">
                            <h3 class="stat-highlight">4 Sectors</h3>
                            <p>Servicing Residential, Office, Commercial, and Institutional spaces.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call-to-Action Section -->
        <section class="audience-section">
            <div class="container text-center max-w-cta">
                <h2 class="section-title">Be Part of the Sustainable Gig Ecosystem</h2>
                <p class="section-subtitle">Whether you are looking for reliable cleaning services or looking to build a sustainable income, YO'gig Maid is built for you.</p>
                
                <div class="cta-button-group">
                    <a href="{{ url('/#book') }}" class="btn btn-primary">Book a Maid Today</a>
                    <a href="{{ url('/#join') }}" class="btn btn-accent">Apply as a Maid</a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Component -->
    @include('components.footer')

</body>
</html>