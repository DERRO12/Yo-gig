<?php

namespace Database\Factories;

use App\Models\MaidProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '+2567'.fake()->unique()->numerify('########'),
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin']);
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'client']);
    }

    /**
     * A maid with a profile in the given verification state.
     */
    public function maid(string $verificationStatus = 'verified'): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'maid'])
            ->afterCreating(function (User $user) use ($verificationStatus): void {
                MaidProfile::factory()->for($user)->create([
                    'verification_status' => $verificationStatus,
                ]);
            });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
