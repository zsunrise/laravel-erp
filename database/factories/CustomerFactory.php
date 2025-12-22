<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition()
    {
        return [
            'name' => fake()->company(),
            'code' => 'CUST' . fake()->unique()->numerify('####'),
            'contact_person' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
            'address' => fake()->address(),
            'credit_limit' => fake()->randomFloat(2, 10000, 100000),
            'payment_days' => fake()->numberBetween(15, 90),
            'rating' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'is_active' => true,
        ];
    }
}

