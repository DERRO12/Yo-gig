<?php

use App\Models\PageSection;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin content screens render', function (string $routeName) {
    $this->seed(SiteContentSeeder::class);

    $this->actingAs($this->admin)->get(route($routeName))->assertOk();
})->with([
    'admin.services.index',
    'admin.services.create',
    'admin.sections.index',
    'admin.sections.create',
    'admin.testimonials.index',
    'admin.testimonials.create',
    'admin.settings.edit',
    'admin.messages.index',
]);

test('admin can update contact settings and they appear on the website', function () {
    $this->actingAs($this->admin)->put(route('admin.settings.update', 'contact'), [
        'contact_email' => 'hello@yogig.test',
        'contact_phone' => '+256 799 123 456',
        'contact_whatsapp' => '',
        'contact_address' => 'Kampala Road',
        'business_hours' => 'Every day',
    ])->assertRedirect(route('admin.settings.edit', 'contact'));

    $this->get('/contact')->assertSee('hello@yogig.test')->assertSee('+256 799 123 456');
});

test('admin can upload a new logo', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->put(route('admin.settings.update', 'general'), [
        'site_name' => 'Yo Gig Cleaners',
        'logo' => UploadedFile::fake()->create('logo.png', 50, 'image/png'),
    ])->assertSessionHasNoErrors();

    $this->get('/')->assertSee('Yo Gig Cleaners');

    $logo = app(SiteSettings::class)->all()['logo'];
    Storage::disk('public')->assertExists($logo);
});

test('admin can create, edit and hide a service with an image', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->post(route('admin.services.store'), [
        'name' => 'Window Washing',
        'category' => 'Residential',
        'short_description' => 'Streak-free windows.',
        'base_price' => 30000,
        'price_unit' => 'per visit',
        'is_active' => '1',
        'image' => UploadedFile::fake()->create('window.jpg', 100, 'image/jpeg'),
    ])->assertRedirect(route('admin.services.index'));

    $service = Service::query()->where('slug', 'window-washing')->sole();
    Storage::disk('public')->assertExists($service->image_path);

    $this->get('/services')->assertSee('Window Washing');

    $this->actingAs($this->admin)->put(route('admin.services.update', $service), [
        'name' => 'Window Washing',
        'base_price' => 35000,
        'price_unit' => 'per visit',
    ])->assertRedirect(route('admin.services.index'));

    expect((float) $service->refresh()->base_price)->toBe(35000.0)
        ->and($service->is_active)->toBeFalse();

    $this->get('/services')->assertDontSee('Window Washing');
});

test('admin can edit homepage content blocks', function () {
    $this->seed(SiteContentSeeder::class);
    $hero = PageSection::query()->where('page', 'home')->where('section', 'hero')->first();

    $this->actingAs($this->admin)->put(route('admin.sections.update', $hero), [
        'page' => 'home',
        'section' => 'hero',
        'title' => 'Fresh homes, fair pay',
        'body' => 'New hero copy',
        'is_active' => '1',
    ])->assertRedirect(route('admin.sections.index', ['page' => 'home']));

    $this->get('/')->assertSee('Fresh homes, fair pay');
});

test('content blocks must target a section the page renders', function () {
    $this->actingAs($this->admin)->post(route('admin.sections.store'), [
        'page' => 'about',
        'section' => 'not-a-section',
        'title' => 'Oops',
    ])->assertSessionHasErrors('section');
});

test('admin can manage testimonials', function () {
    $this->actingAs($this->admin)->post(route('admin.testimonials.store'), [
        'name' => 'Brenda',
        'quote' => 'Reliable every single time.',
        'rating' => 5,
        'is_active' => '1',
    ])->assertRedirect(route('admin.testimonials.index'));

    $this->get('/')->assertSee('Reliable every single time.');

    $testimonial = Testimonial::query()->sole();
    $this->actingAs($this->admin)->delete(route('admin.testimonials.destroy', $testimonial));

    expect(Testimonial::query()->count())->toBe(0);
});

test('admin can verify maids and suspend accounts', function () {
    $maid = User::factory()->maid('pending')->create();

    $this->actingAs($this->admin)->post(route('admin.users.verify', $maid), ['status' => 'verified']);
    expect($maid->maidProfile->refresh()->verification_status)->toBe('verified');

    $this->actingAs($this->admin)->post(route('admin.users.toggle-active', $maid));
    expect($maid->refresh()->is_active)->toBeFalse();

    $this->actingAs($maid)->get(route('maid.dashboard'))->assertRedirect(route('login'));
});

test('non-admins cannot change site content', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)->put(route('admin.settings.update', 'contact'), ['contact_email' => 'evil@example.com'])
        ->assertRedirect('/');

    expect(app(SiteSettings::class)->get('contact_email'))->not->toBe('evil@example.com');
});
