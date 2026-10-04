<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => fake()->uuid(),
            'order_number' => 'ORD-'.strtoupper(fake()->bothify('???####')),
            'customer_name' => fake()->name(),
            'phone' => fake()->numerify('017########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'area' => fake()->word(),
            'payment_method' => 'cod',
            'notes' => null,
            'subtotal' => fake()->randomFloat(2, 200, 2000),
            'delivery_fee' => 60.00,
            'discount' => 0.00,
            'total' => 0.00,
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipping_status' => 'pending',
            'courier_name' => null,
            'courier_tracking_code' => null,
        ];
    }
}
