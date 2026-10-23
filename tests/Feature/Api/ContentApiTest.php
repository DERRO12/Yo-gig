<?php

use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\Service;
use Database\Seeders\SiteContentSeeder;

test('the app config exposes branding, payment accounts and labels', function () {
    $this->getJson(route('api.config'))
        ->assertOk()
        ->assertJsonPath('settings.site_name', 'Yo-gig House Manager')
        ->assertJsonPath('payment_methods.mtn_momo', 'MTN Mobile Money')
        ->assertJsonStructure(['settings' => ['logo', 'currency', 'commission_rate'], 'booking_statuses', 'pages']);
});

test('website pages are served with the same admin-managed content', function (string $page) {
    $this->seed(SiteContentSeeder::class);

    $this->getJson(route('api.pages.show', $page))
        ->assertOk()
        ->assertJsonPath('page', $page)
        ->assertJsonStructure(['sections' => ['hero']]);
})->with(['home', 'about', 'services', 'contact']);

test('the home page lists only funded open gigs', function () {
    Job::factory()->create();
    $funded = Job::factory()->funded()->create();

    $this->getJson(route('api.pages.show', 'home'))
        ->assertJsonPath('total_open_gigs', 1)
        ->assertJsonPath('fresh_gigs.0.id', $funded->id)
        ->assertJsonMissingPath('fresh_gigs.0.client');
});

test('unknown pages and hidden services are not found', function () {
    $hidden = Service::factory()->create(['is_active' => false]);

    $this->getJson(route('api.pages.show', 'nope'))->assertNotFound();
    $this->getJson(route('api.services.show', $hidden))->assertNotFound();
});

test('visitors can send a contact message from the app', function () {
    $this->postJson(route('api.contact'), [
        'name' => 'Visitor',
        'email' => 'visitor@example.com',
        'message' => 'Do you clean offices on Sundays?',
    ])->assertCreated();

    expect(ContactMessage::query()->count())->toBe(1);
});
