<?php

use App\Models\ContactMessage;
use App\Models\Job;
use Database\Seeders\SiteContentSeeder;

beforeEach(function () {
    $this->seed(SiteContentSeeder::class);
});

test('public pages render seeded content', function (string $uri, string $expectedText) {
    $this->get($uri)->assertOk()->assertSee($expectedText, false);
})->with([
    'home' => ['/', 'Spotless Spaces, Protected Payments.'],
    'services' => ['/services', 'Residential Housekeeping'],
    'about' => ['/about', 'Transforming Gig Cleaning Into Sustainable Livelihoods'],
    'contact' => ['/contact', 'Get in Touch'],
    'login' => ['/login', 'Welcome back'],
    'register' => ['/register', 'I want to work'],
]);

test('the homepage only lists gigs that are funded in escrow', function () {
    Job::factory()->funded()->create(['title' => 'Funded kitchen deep clean']);
    Job::factory()->create(['title' => 'Unpaid laundry job']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Funded kitchen deep clean')
        ->assertDontSee('Unpaid laundry job');
});

test('visitors can send a contact message', function () {
    $this->post(route('contact.send'), [
        'name' => 'Amina',
        'email' => 'amina@example.com',
        'message' => 'Do you clean offices on Sundays?',
    ])->assertRedirect(route('contact'));

    expect(ContactMessage::query()->where('email', 'amina@example.com')->exists())->toBeTrue();
});
