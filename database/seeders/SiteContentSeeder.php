<?php

namespace Database\Seeders;

use App\Models\PageSection;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the editable website content with the original copy and images.
 * Safe to run more than once: existing records are left untouched.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedServices();
        $this->seedSections();
        $this->seedTestimonials();
    }

    protected function seedServices(): void
    {
        $services = [
            [
                'name' => 'Residential Housekeeping',
                'category' => 'Residential',
                'short_description' => 'Vetted, trained maids for daily, weekly or one-off home chores, laundry, surface polishing and kitchen cleaning.',
                'image_path' => 'images/training.jpg',
                'base_price' => 40000,
                'price_unit' => 'per visit',
                'price_label' => 'On-demand booking',
                'is_featured' => true,
            ],
            [
                'name' => 'Deep House Cleaning',
                'category' => 'Residential',
                'short_description' => 'Top-to-bottom cleaning of every room, including inside cupboards, appliances, windows and hard-to-reach corners.',
                'image_path' => 'images/cashless.jpg',
                'base_price' => 25000,
                'price_unit' => 'per room',
                'price_label' => 'Priced per room',
                'is_featured' => true,
            ],
            [
                'name' => 'Office & Corporate Cleaning',
                'category' => 'Commercial',
                'short_description' => 'Desk sanitation, floor care, common area disinfection and glass cleaning for productive workplaces.',
                'image_path' => 'images/office.jpg',
                'base_price' => 15000,
                'price_unit' => 'per hour',
                'price_label' => 'Flexible contracts',
                'is_featured' => true,
            ],
            [
                'name' => 'Deep Sanitation & Janitorial',
                'category' => 'Specialized',
                'short_description' => 'Intensive cleaning for commercial complexes, institutions, restrooms and high-traffic public spaces.',
                'image_path' => 'images/office1.jpg',
                'base_price' => 120000,
                'price_unit' => 'per visit',
                'price_label' => 'Custom quotes available',
                'is_featured' => false,
            ],
            [
                'name' => 'Laundry & Ironing',
                'category' => 'Residential',
                'short_description' => 'Washing, drying, ironing and folding done at your home with care for delicate fabrics.',
                'image_path' => 'images/training.jpg',
                'base_price' => 10000,
                'price_unit' => 'per basket',
                'price_label' => 'Priced per basket',
                'is_featured' => false,
            ],
        ];

        foreach ($services as $index => $service) {
            Service::query()->firstOrCreate(
                ['slug' => Str::slug($service['name'])],
                $service + ['sort_order' => $index + 1, 'is_active' => true]
            );
        }
    }

    protected function seedSections(): void
    {
        if (PageSection::query()->exists()) {
            return;
        }

        $sections = [
            // Home: hero slides
            ['home', 'hero', [
                'badge' => 'Instant booking & vetted quality',
                'title' => 'Spotless Spaces, Protected Payments.',
                'body' => 'Book background-checked gig maids for home and office cleaning with complete peace of mind. Your money stays in secure escrow until you approve the finished work.',
                'image_path' => 'images/cashless.jpg',
                'button_text' => 'Book a Maid Today',
                'button_url' => '/client/dashboard',
                'secondary_button_text' => 'Explore Services',
                'secondary_button_url' => '/services',
            ]],
            ['home', 'hero', [
                'badge' => 'Work & earn on your terms',
                'title' => 'Turn Cleaning Skills Into Guaranteed Income.',
                'body' => 'Join our professional gig network. Get skill certification, safety equipment and fast payouts to your mobile money for every job well done.',
                'image_path' => 'images/office.jpg',
                'button_text' => 'Apply as a Maid',
                'button_url' => '/register?role=maid',
                'secondary_button_text' => 'Learn How It Works',
                'secondary_button_url' => '/about',
            ]],
            // Home: escrow steps
            ['home', 'steps', ['badge' => '1', 'title' => 'Book & pay the platform', 'body' => 'Choose a service, pick a date and pay securely. Your money is held in escrow, not handed to anyone yet.']],
            ['home', 'steps', ['badge' => '2', 'title' => 'A vetted maid takes the gig', 'body' => 'ID-verified maids near you claim the funded gig and you see who is coming before they arrive.']],
            ['home', 'steps', ['badge' => '3', 'title' => 'Approve the finished work', 'body' => 'Inspect the job. Once you approve, the platform pays the maid directly. No cash, no disputes.']],
            // Home: CTA
            ['home', 'cta', [
                'title' => 'Ready for a cleaner space?',
                'body' => 'Book in minutes. Pay once. Approve before anyone gets paid.',
                'button_text' => 'Book a Cleaning',
                'button_url' => '/client/dashboard',
                'secondary_button_text' => 'Become a Maid',
                'secondary_button_url' => '/register?role=maid',
            ]],

            // About
            ['about', 'hero', [
                'badge' => 'Our mission & ecosystem',
                'title' => 'Transforming Gig Cleaning Into Sustainable Livelihoods',
                'body' => "YO'gig Maid tackles unemployment by building a professional, safe and cashless gig-cleaning network that connects trained maids with households, offices and commercial spaces.",
                'image_path' => 'images/training.jpg',
            ]],
            ['about', 'mission', ['badge' => 'Mission', 'title' => 'Our Goal', 'body' => 'To reduce poverty by creating flexible, dignified and sustainable self-employment through high-standard cleaning services.']],
            ['about', 'mission', ['badge' => 'Guarantee', 'title' => 'Our Core Promise', 'body' => 'A cashless, escrow-backed ecosystem. Clients get verified quality, and maids receive prompt digital payouts when the job is signed off.']],
            ['about', 'pillars', ['badge' => '01', 'title' => 'Professional Skill Training', 'body' => 'Instruction on sanitation, surface care, deep cleaning and specialized equipment use.']],
            ['about', 'pillars', ['badge' => '02', 'title' => 'Health & Safety Protocols', 'body' => 'Occupational health standards, chemical safety education and protective equipment guidelines.']],
            ['about', 'pillars', ['badge' => '03', 'title' => 'Financial Literacy', 'body' => 'Training in budgeting, savings, record-keeping and income management for lasting security.']],
            ['about', 'pillars', ['badge' => '04', 'title' => 'Customer Service Excellence', 'body' => 'Etiquette, communication and professionalism that keep clients coming back.']],
            ['about', 'stats', ['title' => '200+', 'body' => 'Target beneficiaries trained and onboarded into active gig work.']],
            ['about', 'stats', ['title' => '100%', 'body' => 'Cashless digital payments for zero payment disputes.']],
            ['about', 'stats', ['title' => '4 Sectors', 'body' => 'Residential, office, commercial and institutional spaces.']],
            ['about', 'cta', [
                'title' => 'Be Part of the Sustainable Gig Ecosystem',
                'body' => "Whether you need reliable cleaning or want to build a steady income, YO'gig Maid is built for you.",
                'button_text' => 'Book a Maid Today',
                'button_url' => '/client/dashboard',
                'secondary_button_text' => 'Apply as a Maid',
                'secondary_button_url' => '/register?role=maid',
            ]],

            // Services
            ['services', 'hero', [
                'badge' => 'Professional gig solutions',
                'title' => 'Cleaning Solutions for Every Space',
                'body' => 'From regular home upkeep to commercial deep cleaning, every service is delivered by trained professionals and protected by escrow payments.',
                'image_path' => 'images/office.jpg',
            ]],
            ['services', 'steps', ['badge' => 'Step 01', 'title' => 'Instant Digital Booking', 'body' => 'Schedule online, describe the tasks and see your price upfront with no hidden fees.']],
            ['services', 'steps', ['badge' => 'Step 02', 'title' => 'Vetted Professional Dispatch', 'body' => 'A background-checked, trained maid arrives at your scheduled time.']],
            ['services', 'steps', ['badge' => 'Step 03', 'title' => 'Quality Sign-Off', 'body' => 'Inspect the completed work and approve it only when you are satisfied.']],
            ['services', 'steps', ['badge' => 'Step 04', 'title' => 'Automatic Release', 'body' => 'The platform pays the maid from escrow after your sign-off, so everyone is protected.']],
            ['services', 'cta', [
                'title' => 'Ready to Experience Professional Cleaning?',
                'body' => 'Book your service today or reach out about corporate maintenance packages.',
                'button_text' => 'Book a Service',
                'button_url' => '/client/dashboard',
                'secondary_button_text' => 'Contact Us',
                'secondary_button_url' => '/contact',
            ]],

            // Contact
            ['contact', 'hero', [
                'badge' => "We're here to help",
                'title' => 'Get in Touch',
                'body' => 'Questions about a booking, a payment or joining as a maid? Send us a message and our team will reply quickly.',
                'image_path' => 'images/office1.jpg',
            ]],
        ];

        $order = [];

        foreach ($sections as [$page, $section, $content]) {
            $key = $page.'.'.$section;
            $order[$key] = ($order[$key] ?? 0) + 1;

            PageSection::query()->create($content + [
                'page' => $page,
                'section' => $section,
                'sort_order' => $order[$key],
                'is_active' => true,
            ]);
        }
    }

    protected function seedTestimonials(): void
    {
        if (Testimonial::query()->exists()) {
            return;
        }

        $testimonials = [
            ['name' => 'Sarah N.', 'role' => 'Client, Ntinda', 'quote' => 'I paid through the app and only approved once the house was spotless. No cash, no stress. Booking again next week!'],
            ['name' => 'Grace A.', 'role' => 'Gig Maid, Kireka', 'quote' => 'I know the money is already there before I start. My payouts arrive on my mobile money the same day the client approves.'],
            ['name' => 'Daniel K.', 'role' => 'Office Manager, Kololo', 'quote' => 'Our office cleaning is now reliable and fully documented. The receipts make accounting simple.'],
        ];

        foreach ($testimonials as $index => $testimonial) {
            Testimonial::query()->create($testimonial + ['rating' => 5, 'sort_order' => $index + 1]);
        }
    }
}
