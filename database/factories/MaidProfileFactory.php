<?php

namespace Database\Factories;

use App\Models\MaidProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaidProfile>
 */
class MaidProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'maid']),
            'national_id_number' => 'CM'.fake()->unique()->numerify('############'),
            'national_id_photo_path' => 'national_ids/sample.jpg',
            'verification_status' => 'verified',
            'bio' => fake()->sentence(12),
            'service_area' => fake()->randomElement(['Ntinda', 'Kololo', 'Muyenga', 'Bukoto', 'Naalya']),
            'skills' => ['Deep cleaning', 'Laundry'],
            'available' => true,
            'payout_method' => 'mtn_momo',
            'payout_account_name' => fake()->name(),
            'payout_account_number' => '07'.fake()->numerify('########'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['verification_status' => 'pending']);
    }
}
