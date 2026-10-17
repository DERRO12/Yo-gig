<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\MaidProfile;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Support\BookingWorkflow;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SiteContentSeeder::class);

        User::query()->firstOrCreate(
            ['email' => 'admin@yogigmaid.com'],
            [
                'name' => 'Super Admin',
                'phone_number' => '+256700000001',
                'role' => 'admin',
                'password' => 'password',
            ]
        );

        if (! User::query()->where('email', 'client@yogigmaid.com')->exists()) {
            $this->seedDemoAccounts();
        }

        $this->call(DemoMarketplaceSeeder::class);
    }

    /**
     * Demo client and maid with bookings in every stage of the escrow flow.
     */
    protected function seedDemoAccounts(): void
    {
        $workflow = app(BookingWorkflow::class);

        $client = User::query()->create([
            'name' => 'Demo Client',
            'email' => 'client@yogigmaid.com',
            'phone_number' => '+256700000002',
            'role' => 'client',
            'password' => 'password',
        ]);

        $maid = User::query()->create([
            'name' => 'Demo Maid',
            'email' => 'maid@yogigmaid.com',
            'phone_number' => '+256700000003',
            'role' => 'maid',
            'password' => 'password',
        ]);

        MaidProfile::query()->create([
            'user_id' => $maid->id,
            'national_id_number' => 'CM90000000DEMO',
            'national_id_photo_path' => 'national_ids/demo.jpg',
            'verification_status' => 'verified',
            'service_area' => 'Ntinda',
            'bio' => 'Five years of residential and office cleaning experience.',
            'payout_method' => 'mtn_momo',
            'payout_account_name' => 'Demo Maid',
            'payout_account_number' => '0772000003',
        ]);

        $services = Service::query()->ordered()->get();

        $book = function (Service $service, int $quantity, int $daysAhead) use ($client): Job {
            return Job::query()->create([
                'client_id' => $client->id,
                'service_id' => $service->id,
                'title' => $service->name,
                'description' => 'Please bring your own supplies. Gate code will be shared on arrival.',
                'location' => 'Ntinda, Kampala',
                'quantity' => $quantity,
                'budget' => $service->base_price * $quantity,
                'scheduled_date' => now()->addDays($daysAhead)->toDateString(),
                'scheduled_time' => '09:00',
                'status' => Job::STATUS_OPEN,
                'images' => [],
            ]);
        };

        $pay = function (Job $job, bool $confirm) use ($workflow): Payment {
            $payment = $workflow->submitPayment($job, [
                'method' => 'mtn_momo',
                'payer_phone' => '0772000002',
                'transaction_reference' => 'MP'.random_int(100000, 999999),
            ]);

            if ($confirm) {
                $workflow->confirmPayment($payment);
            }

            return $payment;
        };

        // Awaiting payment
        $book($services[0], 1, 5);

        // Payment submitted, waiting for admin verification
        $pay($book($services[1], 3, 4), false);

        // Funded and open for maids to claim
        $pay($book($services[2], 4, 3), true);

        // Claimed and in progress
        $inProgress = $book($services[0], 1, 1);
        $pay($inProgress, true);
        $workflow->assignMaid($inProgress->refresh(), $maid);
        $workflow->start($inProgress);

        // Completed, waiting for the client to approve
        $done = $book($services[4], 2, 0);
        $pay($done, true);
        $workflow->assignMaid($done->refresh(), $maid);
        $workflow->complete($done);

        // Approved, payout queued for the admin to send
        $approved = $book($services[1], 2, 0);
        $pay($approved, true);
        $workflow->assignMaid($approved->refresh(), $maid);
        $workflow->complete($approved);
        $workflow->approve($approved->refresh(), 5, 'Excellent and on time.');
    }
}
