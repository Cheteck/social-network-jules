<?php

namespace Ijideals\UserProfile\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ijideals\UserProfile\Models\UserProfile;
use App\Models\User; // Main app User model

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Ijideals\UserProfile\Models\UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Ijideals\UserProfile\Models\UserProfile>
     */
    protected $model = UserProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Associates with a User, creating one if not provided
            'bio' => $this->faker->paragraph,
            'website' => $this->faker->optional()->url,
            'location' => $this->faker->optional()->city,
            'birth_date' => $this->faker->optional()->date('Y-m-d', '2005-01-01'), // Ensure user is not too young
        ];
    }
}
