<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name'        => $this->faker->firstName(),
            'last_name'         => $this->faker->lastName(),
            'email'             => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => bcrypt('password'), // أو Hash::make('password')
            'role'              => 'student',          // الافتراضي طالب
            'remember_token'    => Str::random(10),
        ];
    }

    // (اختياري) حالات جاهزة
    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function supervisor(): static
    {
        return $this->state(fn () => ['role' => 'supervisor']);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
