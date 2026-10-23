<?php

/*
|--------------------------------------------------------------------------
| Site Content Definitions
|--------------------------------------------------------------------------
|
| Every setting the super admin can edit lives here, grouped into the tabs
| shown on the admin settings screen. The "default" is used until an admin
| saves a value. Page sections list the content blocks each public page
| renders, so the admin panel knows which blocks it can create.
|
*/

return [

    'settings' => [

        'general' => [
            'label' => 'General & Branding',
            'fields' => [
                'site_name' => ['label' => 'Site name', 'type' => 'text', 'default' => 'Yo-gig House Manager'],
                'site_tagline' => ['label' => 'Tagline', 'type' => 'text', 'default' => 'On-demand cleaning with escrow-protected payments'],
                'meta_description' => ['label' => 'SEO description', 'type' => 'textarea', 'default' => 'Book vetted, background-checked cleaning professionals for home and office spaces. Cashless escrow payments guarantee satisfaction and zero payment risk.'],
                'logo' => ['label' => 'Logo', 'type' => 'image', 'default' => 'images/logo.svg'],
                'footer_about' => ['label' => 'Footer description', 'type' => 'textarea', 'default' => 'Empowering gig cleaning professionals while offering homes and offices reliable, vetted services protected by escrow payments.'],
                'copyright_text' => ['label' => 'Copyright line', 'type' => 'text', 'default' => 'Sustainable Gig Cleaning Ecosystem. All rights reserved.'],
            ],
        ],

        'contact' => [
            'label' => 'Contact Info',
            'fields' => [
                'contact_email' => ['label' => 'Support email', 'type' => 'email', 'default' => 'support@yogighousemanager.com'],
                'contact_phone' => ['label' => 'Phone number', 'type' => 'text', 'default' => '+256 700 000 000'],
                'contact_whatsapp' => ['label' => 'WhatsApp number', 'type' => 'text', 'default' => '+256 700 000 000'],
                'contact_address' => ['label' => 'Office address', 'type' => 'textarea', 'default' => 'Plot 12, Ntinda Road, Kampala, Uganda'],
                'business_hours' => ['label' => 'Business hours', 'type' => 'text', 'default' => 'Mon - Sat, 8:00 AM - 6:00 PM'],
            ],
        ],

        'social' => [
            'label' => 'Social Links',
            'fields' => [
                'social_facebook' => ['label' => 'Facebook URL', 'type' => 'url', 'default' => ''],
                'social_twitter' => ['label' => 'X / Twitter URL', 'type' => 'url', 'default' => ''],
                'social_instagram' => ['label' => 'Instagram URL', 'type' => 'url', 'default' => ''],
                'social_linkedin' => ['label' => 'LinkedIn URL', 'type' => 'url', 'default' => ''],
            ],
        ],

        'payments' => [
            'label' => 'Payments & Payouts',
            'fields' => [
                'currency' => ['label' => 'Currency code', 'type' => 'text', 'default' => 'UGX'],
                'commission_rate' => ['label' => 'Platform commission (%)', 'type' => 'number', 'default' => '15'],
                'min_booking_amount' => ['label' => 'Minimum booking amount', 'type' => 'number', 'default' => '20000'],
                'mtn_momo_number' => ['label' => 'MTN Mobile Money number', 'type' => 'text', 'default' => '0770 000 000'],
                'airtel_money_number' => ['label' => 'Airtel Money number', 'type' => 'text', 'default' => '0750 000 000'],
                'payment_account_name' => ['label' => 'Account name clients pay to', 'type' => 'text', 'default' => 'Yo-gig House Manager Ltd'],
                'bank_details' => ['label' => 'Bank transfer details', 'type' => 'textarea', 'default' => "Bank: Stanbic Bank Uganda\nAccount: 9030 0000 0000\nBranch: Kampala Road"],
                'payment_instructions' => ['label' => 'Payment instructions shown to clients', 'type' => 'textarea', 'default' => 'Send the exact booking amount to any of the accounts below, then submit the transaction ID. Your money is held in escrow and only released to the maid after you approve the finished work.'],
                'auto_confirm_payments' => ['label' => 'Auto-confirm client payments (demo mode)', 'type' => 'boolean', 'default' => '0'],
            ],
        ],

        'maids' => [
            'label' => 'Maid Rules',
            'fields' => [
                'require_maid_verification' => ['label' => 'Maids must be ID-verified before claiming gigs', 'type' => 'boolean', 'default' => '1'],
                'payout_schedule_note' => ['label' => 'Payout schedule note', 'type' => 'textarea', 'default' => 'Payouts are sent to your mobile money or bank account within 24 hours of the client approving your work.'],
            ],
        ],

    ],

    'sections' => [
        'home' => [
            'hero' => 'Hero slides',
            'steps' => 'How escrow works steps',
            'cta' => 'Call to action banner',
        ],
        'about' => [
            'hero' => 'Page header',
            'mission' => 'Mission cards',
            'pillars' => 'How we support maids',
            'stats' => 'Impact numbers',
            'cta' => 'Call to action',
        ],
        'services' => [
            'hero' => 'Page header',
            'steps' => 'Service guarantee steps',
            'cta' => 'Call to action',
        ],
        'contact' => [
            'hero' => 'Page header',
        ],
    ],

];
