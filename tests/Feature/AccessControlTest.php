<?php

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('guests are sent to login from dashboards', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['client.dashboard', 'maid.dashboard', 'admin.dashboard']);

test('clients cannot use maid or admin pages', function () {
    $client = User::factory()->client()->create();
    $job = Job::factory()->funded()->create();

    $this->actingAs($client)->get(route('maid.gigs.index'))->assertRedirect(route('client.dashboard'));
    $this->actingAs($client)->post(route('maid.gigs.claim', $job))->assertRedirect(route('client.dashboard'));
    $this->actingAs($client)->get(route('admin.dashboard'))->assertRedirect('/');

    expect($job->refresh()->maid_id)->toBeNull();
});

test('clients can only see their own bookings', function () {
    $job = Job::factory()->create();
    $otherClient = User::factory()->client()->create();

    $this->actingAs($otherClient)->get(route('client.bookings.show', $job))->assertForbidden();
    $this->actingAs($otherClient)->post(route('client.bookings.payments.store', $job), [
        'method' => 'mtn_momo',
        'payer_phone' => '0772000000',
        'transaction_reference' => 'X',
    ])->assertForbidden();
});

test('maids cannot view unfunded gigs or other maids jobs', function () {
    $maid = User::factory()->maid()->create();
    $otherMaid = User::factory()->maid()->create();

    $unfunded = Job::factory()->create();
    $someoneElses = Job::factory()->assignedTo($otherMaid)->create();

    $this->actingAs($maid)->get(route('maid.gigs.show', $unfunded))->assertNotFound();
    $this->actingAs($maid)->get(route('maid.gigs.show', $someoneElses))->assertNotFound();
    $this->actingAs($maid)->post(route('maid.gigs.start', $someoneElses))->assertForbidden();
});

test('suspended users cannot log in', function () {
    $user = User::factory()->client()->inactive()->create(['email' => 'blocked@example.com']);

    $this->post('/login', ['login' => 'blocked@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('users can log in with their phone number and land on their dashboard', function () {
    $maid = User::factory()->maid()->create(['phone_number' => '+256711111111']);

    $this->post('/login', ['login' => '+256711111111', 'password' => 'password'])
        ->assertRedirect(route('maid.dashboard'));

    $this->assertAuthenticatedAs($maid);
});

test('maid national ID photos are only available to admins', function () {
    $maid = User::factory()->maid()->create();
    $client = User::factory()->client()->create();

    $this->actingAs($client)->get(route('admin.users.national-id', $maid))->assertRedirect('/');
});

test('accounts with $2a$ bcrypt hashes from external tools can still log in', function () {
    $user = User::factory()->admin()->create(['email' => 'legacy@example.com']);
    $legacyHash = '$2a$'.substr(password_hash('secret-123', PASSWORD_BCRYPT), 4);
    DB::table('users')->where('id', $user->id)->update(['password' => $legacyHash]);

    $this->post('/login', ['login' => 'legacy@example.com', 'password' => 'secret-123'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->getAuthPassword())->toStartWith('$2y$');
});
