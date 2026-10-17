<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client(),
            'title' => fake()->randomElement(['Deep house cleaning', 'Office cleaning', 'Laundry and ironing']),
            'description' => fake()->sentence(15),
            'location' => fake()->randomElement(['Ntinda, Kampala', 'Kololo, Kampala', 'Muyenga, Kampala']),
            'budget' => 50000,
            'quantity' => 1,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
            'status' => Job::STATUS_OPEN,
            'payment_status' => Job::PAYMENT_UNPAID,
            'images' => [],
        ];
    }

    /**
     * Payment received and held in escrow.
     */
    public function funded(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => Job::PAYMENT_PAID,
            'platform_fee' => 7500,
            'maid_earnings' => 42500,
        ]);
    }

    public function assignedTo(User $maid): static
    {
        return $this->funded()->state(fn (array $attributes) => [
            'maid_id' => $maid->id,
            'status' => Job::STATUS_MATCHED,
            'claimed_at' => now(),
        ]);
    }

    public function completedBy(User $maid): static
    {
        return $this->assignedTo($maid)->state(fn (array $attributes) => [
            'status' => Job::STATUS_COMPLETED,
            'started_at' => now()->subHours(3),
            'completed_at' => now(),
        ]);
    }
}
