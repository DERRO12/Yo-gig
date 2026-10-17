<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maid_id' => User::factory()->maid(),
            'job_id' => fn (array $attributes) => Job::factory()->completedBy(User::find($attributes['maid_id'])),
            'amount' => 42500,
            'method' => 'mtn_momo',
            'account_name' => fake()->name(),
            'account_number' => '07'.fake()->numerify('########'),
            'status' => Payout::STATUS_PENDING,
        ];
    }
}
