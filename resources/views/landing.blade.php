<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YO GIG MAID - Professional Gig Cleaning Services</title>
    
    <!-- External Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <!-- Include Navbar Component -->
    @include('components.navbar')

    <main>
        <section class="hero">
            <div class="hero-text">
                <h1>Reliable Cleaning Services at Your Fingertips</h1>
                <p>Connecting trained, professional gig cleaners with residential, office, and commercial spaces[cite: 1]. Empowering livelihoods, one space at a time[cite: 1].</p>
                <div class="hero-buttons">
                    <a href="#" class="btn btn-primary">Book a Cleaner</a>
                    <a href="#" class="btn btn-accent">Become a Cleaner</a>
                </div>
            </div>
        </section>

        <section id="services" class="services-section">
            <h2 class="section-title">Our Cleaning Solutions</h2>
            <div class="services-grid">
                <div class="service-card">
                    <h3>Residential Cleaning</h3>
                    <p>House, apartment, move-in/move-out, and deep cleaning tailored to your household[cite: 1].</p>
                </div>
                <div class="service-card">
                    <h3>Office Cleaning</h3>
                    <p>Desks, washrooms, reception, and scheduled workspace maintenance for businesses[cite: 1].</p>
                </div>
                <div class="service-card">
                    <h3>Commercial Cleaning</h3>
                    <p>Dedicated cleaning services for shops, restaurants, and rental property management[cite: 1].</p>
                </div>
                <div class="service-card">
                    <h3>Institutional Cleaning</h3>
                    <p>Trusted cleaning for schools, non-profits, and community centers[cite: 1].</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Include Footer Component -->
    @include('components.footer')

</body>
</html>