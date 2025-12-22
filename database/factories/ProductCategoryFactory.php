<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition()
    {
        return [
            'name' => fake()->words(2, true),
            'code' => 'CAT' . fake()->unique()->numerify('####'),
            'parent_id' => null,
            'sort' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}

