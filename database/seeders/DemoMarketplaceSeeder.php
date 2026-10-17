<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\MaidProfile;
use App\Models\Service;
use App\Models\User;
use App\Support\BookingWorkflow;
use Illuminate\Database\Seeder;

/**
 * Fills the marketplace with realistic demo clients, maids and gigs so every
 * page of the site has something to show. Safe to run more than once.
 */
class DemoMarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('email', 'grace@demo.yogigmaid.com')->exists()) {
            return;
        }

        $workflow = app(BookingWorkflow::class);
        $services = Service::query()->ordered()->get()->keyBy('slug');

        if ($services->isEmpty()) {
            return;
        }

        $maids = collect([
            ['Grace Achieng', 'grace', 'Ntinda', 'Five years of home cleaning and laundry. Known for spotless kitchens.'],
            ['Sarah Nakato', 'sarah', 'Kololo', 'Office and corporate cleaning specialist, trained in safe chemical handling.'],
            ['Brenda Auma', 'brenda', 'Muyenga', 'Deep cleaning and move-out cleans. Brings her own supplies.'],
            ['Joyce Namuli', 'joyce', 'Bukoto', 'Reliable weekly housekeeping and ironing.'],
        ])->map(function (array $maid, int $index): User {
            [$name, $slug, $area, $bio] = $maid;

            $user = User::query()->create([
                'name' => $name,
                'email' => "{$slug}@demo.yogigmaid.com",
                'phone_number' => '+25677100000'.$index,
                'role' => 'maid',
                'password' => 'password',
            ]);

            MaidProfile::query()->create([
                'user_id' => $user->id,
                'national_id_number' => 'CM9DEMO00000'.$index,
                'national_id_photo_path' => 'national_ids/demo.jpg',
                'verification_status' => 'verified',
                'service_area' => $area,
                'bio' => $bio,
                'skills' => ['Deep cleaning', 'Laundry', 'Kitchen care'],
                'payout_method' => $index % 2 === 0 ? 'mtn_momo' : 'airtel_money',
                'payout_account_name' => $name,
                'payout_account_number' => '07'.(70000000 + $index * 1111),
            ]);

            return $user;
        });

        $clients = collect([
            ['Daniel Kato', 'daniel'],
            ['Amina Nanyonga', 'amina'],
            ['Peter Okello', 'peter'],
        ])->map(fn (array $client, int $index): User => User::query()->create([
            'name' => $client[0],
            'email' => "{$client[1]}@demo.yogigmaid.com",
            'phone_number' => '+25678200000'.$index,
            'role' => 'client',
            'password' => 'password',
        ]));

        $book = function (User $client, string $serviceSlug, int $quantity, string $title, string $location, int $daysAhead, string $notes) use ($services, $workflow): ?Job {
            $service = $services->get($serviceSlug) ?? $services->first();

            $job = Job::query()->create([
                'client_id' => $client->id,
                'service_id' => $service->id,
                'title' => $title,
                'description' => $notes,
                'location' => $location,
                'quantity' => $quantity,
                'budget' => max((float) $service->base_price * $quantity, 30000),
                'scheduled_date' => now()->addDays($daysAhead)->toDateString(),
                'scheduled_time' => ['08:00', '09:30', '11:00', '14:00'][abs($daysAhead) % 4],
                'status' => Job::STATUS_OPEN,
                'images' => [],
            ]);

            $payment = $workflow->submitPayment($job, [
                'method' => 'mtn_momo',
                'payer_phone' => $client->phone_number,
                'transaction_reference' => 'MP'.random_int(1000000, 9999999),
            ]);
            $workflow->confirmPayment($payment);

            return $job->refresh();
        };

        // Funded gigs waiting for maids (shown on the homepage and the maid gig board)
        $book($clients[0], 'deep-house-cleaning', 4, '4-bedroom deep clean before family visit', 'Ntinda, Kampala', 2, 'Focus on kitchen, bathrooms and windows. Mop and bucket available.');
        $book($clients[1], 'residential-housekeeping', 1, 'Weekly housekeeping for a 2-bedroom apartment', 'Kololo, Kampala', 1, 'Dusting, mopping, dishes and changing bed sheets.');
        $book($clients[2], 'office-corporate-cleaning', 6, 'Small office cleaning, 6 hours', 'Nakasero, Kampala', 3, '10 desks, a kitchenette and two washrooms. Start after 5pm if possible.');
        $book($clients[0], 'laundry-ironing', 3, 'Laundry and ironing, 3 baskets', 'Ntinda, Kampala', 4, 'Mostly school uniforms and office shirts.');
        $book($clients[1], 'deep-sanitation-janitorial', 1, 'Post-construction clean of a new shop', 'Muyenga, Kampala', 5, 'Dust everywhere after renovations. Floors and glass front need care.');
        $book($clients[2], 'deep-house-cleaning', 3, 'Move-out clean, 3 rooms', 'Bukoto, Kampala', 6, 'Landlord inspection the next day, must be thorough.');

        // Completed and approved jobs (fill payouts, ratings and earnings)
        $reviews = [
            [5, 'Grace was on time and the house was spotless. Highly recommend!'],
            [5, 'Very professional office clean. We have booked Sarah again.'],
            [4, 'Great work on the deep clean. Missed one window but fixed it immediately.'],
            [5, 'Ironing was perfect. Will book every week.'],
        ];

        foreach ($maids as $index => $maid) {
            $job = $book($clients[$index % 3], ['residential-housekeeping', 'office-corporate-cleaning', 'deep-house-cleaning', 'laundry-ironing'][$index], 2, 'Completed: '.['home housekeeping', 'office clean', 'deep clean', 'laundry day'][$index], $maid->maidProfile->service_area.', Kampala', -($index + 2), 'Demo completed job.');

            $workflow->assignMaid($job, $maid);
            $workflow->start($job);
            $workflow->complete($job);
            $payout = $workflow->approve($job->refresh(), $reviews[$index][0], $reviews[$index][1]);

            if ($index < 2) {
                $payout->update(['status' => 'paid', 'reference' => 'PO'.random_int(100000, 999999), 'paid_at' => now()->subDay()]);
            }
        }

        // One gig claimed and in progress
        $active = $book($clients[1], 'residential-housekeeping', 1, 'Saturday morning housekeeping', 'Kololo, Kampala', 0, 'Two kids at home, please use child-safe products.');
        $workflow->assignMaid($active, $maids[1]);
        $workflow->start($active);
    }
}
