<?php

namespace Database\Factories;

use App\Models\Utilisateur;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class UserFactory extends Factory
{
    protected $model = Utilisateur::class;

    
    protected static ?string $password;

    
    public function definition(): array
    {
        return [
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['Admin', 'Conducteur']),
            'motdePasse' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

   
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => []);
    }
}
