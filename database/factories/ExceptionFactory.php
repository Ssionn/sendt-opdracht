<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Exception as ExceptionModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExceptionModel>
 */
class ExceptionFactory extends Factory
{
    protected $model = ExceptionModel::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'message'     => fake()->sentence(),
            'stack_trace' => fake()->text(500),
            'file'        => fake()->filePath(),
            'line'        => fake()->numberBetween(1, 1000),
        ];
    }
}
