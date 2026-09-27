<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->words(3, true), 'description' => fake()->paragraph(), 'price' => '1250.50', 'rating' => 4.5, 'sizes' => [], 'colors' => []];
    }
}
