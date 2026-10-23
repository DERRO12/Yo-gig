<?php

use App\Models\Job;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Service;
use App\Models\User;
use App\Support\SiteSettings;

test('the escrow flow works end to end through the app API', function () {
    app(SiteSettings::class)->set(['commission_rate' => '10']);

    $client = User::factory()->client()->create();
    $maid = User::factory()->maid()->create();
    $admin = User::factory()->admin()->create();
    $service = Service::factory()->create(['base_price' => 30000]);

    // Client books two units of a fixed-price service
    $jobId = apiAs($client)->postJson(route('api.client.bookings.store'), [
        'service_id' => $service->id,
        'quantity' => 2,
        'location' => 'Ntinda, Kampala',
        'scheduled_date' => now()->addDay()->toDateString(),
        'scheduled_time' => '09:30',
    ])->assertCreated()
        ->assertJsonPath('data.budget', 60000)
        ->assertJsonPath('data.needs_payment', true)
        ->json('data.id');

    // Client pays; admin confirms and the gig becomes visible to maids
    apiAs($client)->postJson(route('api.client.bookings.payments.store', $jobId), [
        'method' => 'airtel_money',
        'payer_phone' => '0750000000',
        'transaction_reference' => 'AM123',
    ])->assertOk()->assertJsonPath('data.payment_status', Job::PAYMENT_PENDING);

    apiAs($maid)->getJson(route('api.maid.gigs.index'))
        ->assertJsonCount(0, 'area_gigs')
        ->assertJsonCount(0, 'other_gigs');

    apiAs($admin)->postJson(route('api.admin.payments.confirm', Payment::query()->sole()))->assertOk();

    $gigs = apiAs($maid)->getJson(route('api.maid.gigs.index'))->assertJsonPath('can_claim', true);
    expect(collect([...$gigs->json('area_gigs'), ...$gigs->json('other_gigs')])->pluck('id')->all())->toBe([$jobId]);

    // Client contact details stay hidden until the maid claims the gig
    apiAs($maid)->getJson(route('api.maid.gigs.show', $jobId))->assertJsonMissingPath('data.client');

    apiAs($maid)->postJson(route('api.maid.gigs.claim', $jobId))
        ->assertOk()
        ->assertJsonPath('data.client.phone_number', $client->phone_number);

    apiAs($maid)->postJson(route('api.maid.gigs.start', $jobId))->assertJsonPath('data.status', Job::STATUS_IN_PROGRESS);
    apiAs($maid)->postJson(route('api.maid.gigs.complete', $jobId))->assertJsonPath('data.is_awaiting_client_approval', true);

    // Client approves and the maid's payout is queued
    apiAs($client)->postJson(route('api.client.bookings.approve', $jobId), ['rating' => 5])
        ->assertOk()
        ->assertJsonPath('data.payment_status', Job::PAYMENT_RELEASED);

    apiAs($maid)->getJson(route('api.maid.earnings'))
        ->assertJsonPath('totals.pending', 54000)
        ->assertJsonPath('payouts.0.status', Payout::STATUS_PENDING);
});

test('clients cannot see or act on other clients bookings', function () {
    $job = Job::factory()->create();
    $otherClient = User::factory()->client()->create();

    apiAs($otherClient)->getJson(route('api.client.bookings.show', $job))->assertForbidden();
    apiAs($otherClient)->postJson(route('api.client.bookings.cancel', $job))->assertForbidden();
});

test('business rule failures come back as JSON messages', function () {
    $maid = User::factory()->maid()->create();
    $job = Job::factory()->funded()->create();

    apiAs($maid)->postJson(route('api.maid.gigs.start', $job))->assertForbidden();

    apiAs($job->client)->postJson(route('api.client.bookings.approve', $job))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This booking is not waiting for your approval.');
});

test('unverified maids cannot claim gigs from the app', function () {
    $maid = User::factory()->maid('pending')->create();
    $job = Job::factory()->funded()->create();

    apiAs($maid)->postJson(route('api.maid.gigs.claim', $job))->assertUnprocessable();

    expect($job->refresh()->maid_id)->toBeNull();
});

test('maids save payout details from the app', function () {
    $maid = User::factory()->maid()->create();

    apiAs($maid)->putJson(route('api.maid.earnings.payout-details'), [
        'payout_method' => 'mtn_momo',
        'payout_account_name' => 'Grace A.',
        'payout_account_number' => '0772123456',
    ])->assertOk()->assertJsonPath('data.maid_profile.has_payout_details', true);
});
