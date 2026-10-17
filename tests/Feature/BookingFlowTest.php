<?php

use App\Models\Job;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Service;
use App\Models\User;
use App\Support\SiteSettings;

test('a client books a fixed-price service and is charged price times quantity', function () {
    $client = User::factory()->client()->create();
    $service = Service::factory()->create(['base_price' => 25000, 'price_unit' => 'per room']);

    $response = $this->actingAs($client)->post(route('client.bookings.store'), [
        'service_id' => $service->id,
        'quantity' => 3,
        'budget' => 1, // ignored for fixed-price services
        'location' => 'Ntinda, Kampala',
        'scheduled_date' => now()->addDay()->toDateString(),
        'scheduled_time' => '10:00',
    ]);

    $job = Job::query()->sole();

    $response->assertRedirect(route('client.bookings.show', $job));
    expect((float) $job->budget)->toBe(75000.0)
        ->and($job->title)->toBe($service->name)
        ->and($job->payment_status)->toBe(Job::PAYMENT_UNPAID)
        ->and($job->reference)->toStartWith('YG-');
});

test('bookings below the minimum amount are rejected', function () {
    app(SiteSettings::class)->set(['min_booking_amount' => '50000']);
    $client = User::factory()->client()->create();
    $service = Service::factory()->create(['base_price' => 10000]);

    $this->actingAs($client)->post(route('client.bookings.store'), [
        'service_id' => $service->id,
        'quantity' => 1,
        'location' => 'Kololo',
        'scheduled_date' => now()->addDay()->toDateString(),
        'scheduled_time' => '10:00',
    ])->assertSessionHasErrors('budget');

    expect(Job::query()->count())->toBe(0);
});

test('the full escrow flow: client pays, admin confirms, maid works, client approves, admin pays out', function () {
    app(SiteSettings::class)->set(['commission_rate' => '20']);

    $admin = User::factory()->admin()->create();
    $maid = User::factory()->maid()->create();
    $job = Job::factory()->create(['budget' => 100000]);
    $client = $job->client;

    // Client submits payment
    $this->actingAs($client)->post(route('client.bookings.payments.store', $job), [
        'method' => 'mtn_momo',
        'payer_phone' => '0772000000',
        'transaction_reference' => 'MP123456',
    ])->assertSessionHasNoErrors();

    $payment = Payment::query()->sole();
    expect($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($job->refresh()->payment_status)->toBe(Job::PAYMENT_PENDING);

    // Unfunded gigs cannot be claimed yet
    $this->actingAs($maid)->post(route('maid.gigs.claim', $job))->assertSessionHas('error');
    expect($job->refresh()->maid_id)->toBeNull();

    // Admin confirms the payment: money is now held in escrow with fees locked in
    $this->actingAs($admin)->post(route('admin.payments.confirm', $payment))->assertSessionHas('success');

    $job->refresh();
    expect($job->payment_status)->toBe(Job::PAYMENT_PAID)
        ->and((float) $job->platform_fee)->toBe(20000.0)
        ->and((float) $job->maid_earnings)->toBe(80000.0)
        ->and($payment->refresh()->verified_by)->toBe($admin->id);

    // Maid claims, starts and completes
    $this->actingAs($maid)->post(route('maid.gigs.claim', $job))->assertRedirect(route('maid.gigs.show', $job));
    $this->actingAs($maid)->post(route('maid.gigs.start', $job));
    $this->actingAs($maid)->post(route('maid.gigs.complete', $job));

    expect($job->refresh()->isAwaitingClientApproval())->toBeTrue();

    // Client approves and rates: escrow released, payout queued for the maid
    $this->actingAs($client)->post(route('client.bookings.approve', $job), ['rating' => 4, 'review' => 'Great'])
        ->assertSessionHas('success');

    $job->refresh();
    $payout = Payout::query()->sole();

    expect($job->payment_status)->toBe(Job::PAYMENT_RELEASED)
        ->and($job->client_rating)->toBe(4)
        ->and($payout->maid_id)->toBe($maid->id)
        ->and((float) $payout->amount)->toBe(80000.0)
        ->and($payout->status)->toBe(Payout::STATUS_PENDING)
        ->and($payout->account_number)->toBe($maid->maidProfile->payout_account_number)
        ->and($maid->maidProfile->refresh()->completed_jobs)->toBe(1);

    // Admin sends the money and records it
    $this->actingAs($admin)->post(route('admin.payouts.paid', $payout), ['reference' => 'TX-998'])->assertSessionHas('success');

    expect($payout->refresh()->status)->toBe(Payout::STATUS_PAID)
        ->and($payout->reference)->toBe('TX-998');

    $this->actingAs($maid)->get(route('maid.earnings'))->assertOk()->assertSee('TX-998');
});

test('auto-confirm mode puts payments straight into escrow', function () {
    app(SiteSettings::class)->set(['auto_confirm_payments' => '1']);
    $job = Job::factory()->create();

    $this->actingAs($job->client)->post(route('client.bookings.payments.store', $job), [
        'method' => 'bank_transfer',
        'transaction_reference' => 'BANK-1',
    ])->assertSessionHasNoErrors();

    expect($job->refresh()->payment_status)->toBe(Job::PAYMENT_PAID)
        ->and((float) $job->platform_fee)->toBe(7500.0) // default 15% commission
        ->and((float) $job->maid_earnings)->toBe(42500.0);
});

test('a rejected payment asks the client to pay again', function () {
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->create(['payment_status' => Job::PAYMENT_PENDING]);
    $payment = Payment::factory()->for($job)->create();

    $this->actingAs($admin)->post(route('admin.payments.reject', $payment), ['notes' => 'Not found']);

    expect($payment->refresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and($job->refresh()->payment_status)->toBe(Job::PAYMENT_UNPAID);

    $this->actingAs($job->client)->get(route('client.bookings.show', $job))->assertSee('could not be verified');
});

test('unverified maids cannot claim gigs', function () {
    $maid = User::factory()->maid('pending')->create();
    $job = Job::factory()->funded()->create();

    $this->actingAs($maid)->post(route('maid.gigs.claim', $job))->assertSessionHas('error');

    expect($job->refresh()->maid_id)->toBeNull();
});

test('cancelling a paid booking marks a refund as due and the admin can settle it', function () {
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->funded()->create();
    Payment::factory()->for($job)->create(['status' => Payment::STATUS_SUCCESSFUL]);

    $this->actingAs($job->client)->post(route('client.bookings.cancel', $job))->assertRedirect(route('client.dashboard'));

    expect($job->refresh()->status)->toBe(Job::STATUS_CANCELLED)
        ->and($job->payment_status)->toBe(Job::PAYMENT_REFUND_DUE);

    $this->actingAs($admin)->post(route('admin.bookings.status', $job), ['action' => 'refund']);

    expect($job->refresh()->payment_status)->toBe(Job::PAYMENT_REFUNDED)
        ->and($job->payments()->first()->status)->toBe(Payment::STATUS_REFUNDED);
});

test('maids can release a claimed gig back to the pool but not one in progress', function () {
    $maid = User::factory()->maid()->create();
    $claimed = Job::factory()->assignedTo($maid)->create();
    $started = Job::factory()->assignedTo($maid)->create(['status' => Job::STATUS_IN_PROGRESS]);

    $this->actingAs($maid)->post(route('maid.gigs.release', $claimed));
    $this->actingAs($maid)->post(route('maid.gigs.release', $started))->assertSessionHas('error');

    expect($claimed->refresh()->status)->toBe(Job::STATUS_OPEN)
        ->and($claimed->maid_id)->toBeNull()
        ->and($started->refresh()->maid_id)->toBe($maid->id);
});

test('dashboards render for each role', function () {
    $maid = User::factory()->maid()->create();
    $job = Job::factory()->completedBy($maid)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($job->client)->get(route('client.dashboard'))->assertOk()->assertSee($job->title);
    $this->actingAs($job->client)->get(route('client.bookings.show', $job))->assertOk()->assertSee('Approve & release payment', false);
    $this->actingAs($job->client)->get(route('client.bookings.create'))->assertOk();
    $this->actingAs($maid)->get(route('maid.dashboard'))->assertOk()->assertSee($job->title);
    $this->actingAs($maid)->get(route('maid.gigs.index'))->assertOk();
    $this->actingAs($maid)->get(route('maid.gigs.show', $job))->assertOk();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk()->assertSee($job->reference);
    $this->actingAs($admin)->get(route('admin.bookings.show', $job))->assertOk();
    $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.payouts.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.show', $maid))->assertOk();
    $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
});
