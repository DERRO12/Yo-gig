<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'client_id' => fn (array $attributes) => Job::find($attributes['job_id'])->client_id,
            'amount' => fn (array $attributes) => Job::find($attributes['job_id'])->budget,
            'method' => 'mtn_momo',
            'provider' => 'MTN Mobile Money',
            'payer_phone' => '0770'.fake()->numerify('######'),
            'transaction_reference' => strtoupper(fake()->bothify('MP######??')),
            'status' => Payment::STATUS_PENDING,
        ];
    }
}
