<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services | YO'gig Maid - Professional & Escrow-Backed Cleaning</title>
    
    <!-- Meta Description for SEO -->
    <meta name="description" content="Explore YO'gig Maid's professional cleaning solutions including residential housekeeping, office sanitation, deep commercial cleaning, and escrow-backed payments.">
    
    <!-- External Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <!-- Header & Navigation -->
    @include('components.navbar')

    <main>
        <!-- Services Hero Header with office.jpeg Overlay -->
        <section 
            class="about-hero hero-bg-overlay services-hero-bg" 
            style="--hero-bg: url('{{ asset('images/office.jpeg') }}');"
        >
            <div class="container text-center">
                <div class="badge-pill badge-pill-center badge-pill-light">
                    <span>Professional Gig Solutions</span>
                </div>
                <h1 class="hero-title">Tailored Cleaning Solutions for Every Space</h1>
                <p class="hero-subtitle">
                    From regular residential upkeep to specialized commercial deep cleaning, all services are delivered by trained professionals and secured with our cashless escrow protection.
                </p>
            </div>
        </section>

        <!-- Main Services Showcase Grid -->
        <section class="services-section bg-white">
            <div class="container">
                <div class="section-header-center">
                    <h2 class="section-title">Our Service Offerings</h2>
                    <p class="section-subtitle">Select from our flexible service packages designed to meet high standards of hygiene and safety.</p>
                </div>

                <div class="services-grid">
                    <!-- Service 1: Office & Commercial Cleaning (office.jpeg) -->
                    <article class="service-card-image">
                        <div class="service-card-img-wrapper">
                            <img src="{{ asset('images/office.jpeg') }}" alt="Office and Commercial Cleaning Services">
                        </div>
                        <div class="service-card-body">
                            <span class="card-number">Commercial</span>
                            <h3>Office & Corporate Cleaning</h3>
                            <p>Routine desk sanitation, floor maintenance, common area disinfection, and glass cleaning tailored for productive work environments.</p>
                            <span class="price-tag">Flexible Contracts</span>
                        </div>
                    </article>

                    <!-- Service 2: Deep Sanitation & Janitorial (office1.jpeg) -->
                    <article class="service-card-image">
                        <div class="service-card-img-wrapper">
                            <img src="{{ asset('images/office1.jpeg') }}" alt="Deep Janitorial and Facility Maintenance">
                        </div>
                        <div class="service-card-body">
                            <span class="card-number">Specialized</span>
                            <h3>Deep Sanitation & Janitorial</h3>
                            <p>Intensive deep cleaning for commercial complexes, institutional facilities, restrooms, and high-traffic public spaces.</p>
                            <span class="price-tag">Custom Quotes</span>
                        </div>
                    </article>

                    <!-- Service 3: Professional Maid Training & Vetting (training.jpg) -->
                    <article class="service-card-image">
                        <div class="service-card-img-wrapper">
                            <img src="{{ asset('images/training.jpg') }}" alt="Trained and Verified Maids">
                        </div>
                        <div class="service-card-body">
                            <span class="card-number">Residential</span>
                            <h3>Residential Housekeeping</h3>
                            <p>Vetted, highly-trained Maids for daily, weekly, or gig-based home chores, laundry care, surface polishing, and kitchen cleaning.</p>
                            <span class="price-tag">On-Demand Booking</span>
                        </div>
                    </article>

                    <!-- Service 4: Cashless Escrow Ecosystem (cashless.jpg) -->
                    <article class="service-card-image">
                        <div class="service-card-img-wrapper">
                            <img src="{{ asset('images/cashless.jpg') }}" alt="Safe Cashless Escrow Payments">
                        </div>
                        <div class="service-card-body">
                            <span class="card-number">Secure Payments</span>
                            <h3>Cashless Escrow Guarantee</h3>
                            <p>Every booking is protected by digital escrow. Funds are held safely and only released when you sign off on job completion.</p>
                            <span class="price-tag">100% Protection</span>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Service Execution Standard / Steps Section -->
        <section class="services-section">
            <div class="container">
                <div class="section-header-center">
                    <h2 class="section-title">The YO'gig Service Guarantee</h2>
                    <p class="section-subtitle">How we deliver consistent quality and total peace of mind for every client booking.</p>
                </div>

                <div class="services-grid">
                    <article class="service-card">
                        <span class="card-number">Step 01</span>
                        <h3>Instant Digital Booking</h3>
                        <p>Schedule your service online, specify task requirements, and lock in your price upfront without hidden fees.</p>
                    </article>

                    <article class="service-card">
                        <span class="card-number">Step 02</span>
                        <h3>Vetted Professional Dispatch</h3>
                        <p>A background-checked, professionally trained Maid equipped with standard supplies arrives at your scheduled time.</p>
                    </article>

                    <article class="service-card">
                        <span class="card-number">Step 03</span>
                        <h3>Quality Sign-Off</h3>
                        <p>Inspect the completed service against our digital checklist to ensure total satisfaction before approving payment.</p>
                    </article>

                    <article class="service-card">
                        <span class="card-number">Step 04</span>
                        <h3>Automated Release</h3>
                        <p>Payment is seamlessly transferred from escrow to the worker's wallet upon your sign-off, ensuring trust and security.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Escrow Protection Highlight with cashless.jpg Overlay -->
        <section class="escrow-section escrow-bg-overlay" style="--escrow-img: url('{{ asset('images/cashless.jpg') }}');">
            <div class="container">
                <div class="escrow-card text-center">
                    <h2 class="impact-title">Transparent & Dispute-Free Booking</h2>
                    <p class="impact-description">
                        We eliminate cash hassle and payment disputes by holding funds in secure digital escrow until the work is thoroughly inspected and approved.
                    </p>

                    <div class="cta-button-group">
                        <a href="{{ url('/#book') }}" class="btn btn-accent">Book a Service Now</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call-to-Action Section -->
        <section class="audience-section">
            <div class="container text-center max-w-cta">
                <h2 class="section-title">Ready to Experience Professional Cleaning?</h2>
                <p class="section-subtitle">Book your service today or reach out to discuss custom corporate maintenance packages.</p>
                
                <div class="cta-button-group">
                    <a href="{{ url('/#book') }}" class="btn btn-primary">Book a Service</a>
                    <a href="{{ url('/#contact') }}" class="btn btn-accent">Contact Support</a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Component -->
    @include('components.footer')

</body>
</html>