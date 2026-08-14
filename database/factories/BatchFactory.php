<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        $stock = $this->faker->numberBetween(10, 100);
        return [
            'product_id' => Product::inRandomOrder()->first()?->id ?? Product::factory(),
            'batch_number' => 'LOT-' . strtoupper($this->faker->bothify('??###??')),
            'stock' => $stock,
            'initial_stock' => $stock + $this->faker->numberBetween(0, 50),
            'expiration_date' => $this->faker->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
            'active' => true,
            'user_created' => User::first()?->id ?? 1,
            'user_updated' => null,
        ];
    }
}
