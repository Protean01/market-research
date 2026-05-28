<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        $birthYear = fake()->numberBetween(1960, 2008);

        return [
            'user_id' => User::factory(),
            'birth_year' => $birthYear,
            'gender' => fake()->randomElement(['Male', 'Female', 'Other']),
            'location' => fake()->randomElement(['Lusaka', 'Ndola', 'Kitwe', 'Kabwe', 'Chingola']),
            'employment' => fake()->randomElement(['Employed', 'Self-employed', 'Student', 'Unemployed']),
            'income_band' => fake()->randomElement(['Under 2000', '2000 - 4999', '5000 - 9999', '10000+']),
            'language' => fake()->randomElement(['English', 'Bemba', 'Nyanja']),
            'is_complete' => true,
            'current_streak' => fake()->numberBetween(0, 5),
            'longest_streak' => fake()->numberBetween(5, 10),
            'trust_score' => fake()->numberBetween(80, 100),
        ];
    }
}
