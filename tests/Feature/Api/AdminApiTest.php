<?php

use App\Models\ApiToken;
use App\Models\Job;
use App\Models\Service;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->withToken(ApiToken::issue($this->admin));
});

test('admin list endpoints respond', function (string $routeName) {
    $this->seed(SiteContentSeeder::class);
    Job::factory()->create();

    $this->getJson(route($routeName))->assertOk();
})->with([
    'api.admin.dashboard',
    'api.admin.bookings.index',
    'api.admin.payments.index',
    'api.admin.payouts.index',
    'api.admin.users.index',
    'api.admin.services.index',
    'api.admin.sections.index',
    'api.admin.testimonials.index',
    'api.admin.settings.index',
    'api.admin.messages.index',
]);

test('admin creates a service with an image from the app', function () {
    Storage::fake('public');

    $this->post(route('api.admin.services.store'), [
        'name' => 'Window Washing',
        'base_price' => 15000,
        'price_unit' => 'per window',
        'is_active' => '1',
        'image' => UploadedFile::fake()->create('window.jpg', 100, 'image/jpeg'),
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.slug', 'window-washing');

    Storage::disk('public')->assertExists(Service::query()->sole()->image_path);
});

test('admin updates settings and the public config reflects them', function () {
    $this->putJson(route('api.admin.settings.update', 'general'), [
        'site_name' => 'Yo-gig House Manager',
        'site_tagline' => 'Trusted home help',
    ])->assertOk();

    $this->getJson(route('api.config'))
        ->assertJsonPath('settings.site_tagline', 'Trusted home help')
        ->assertJsonPath('settings.site_name', 'Yo-gig House Manager');

    expect(app(SiteSettings::class)->get('site_tagline'))->toBe('Trusted home help');
});

test('admin verifies a maid and suspends accounts', function () {
    $maid = User::factory()->maid('pending')->create();

    $this->postJson(route('api.admin.users.verify', $maid), ['status' => 'verified'])
        ->assertOk()
        ->assertJsonPath('data.maid_profile.verification_status', 'verified');

    $this->postJson(route('api.admin.users.toggle-active', $maid))->assertJsonPath('data.is_active', false);
    $this->postJson(route('api.admin.users.toggle-active', $this->admin))->assertUnprocessable();
});

test('admin assigns a verified maid to a booking', function () {
    $maid = User::factory()->maid()->create();
    $job = Job::factory()->funded()->create();

    $this->postJson(route('api.admin.bookings.assign', $job), ['maid_id' => $maid->id])
        ->assertOk()
        ->assertJsonPath('data.maid.id', $maid->id)
        ->assertJsonPath('data.status', Job::STATUS_MATCHED);
});
