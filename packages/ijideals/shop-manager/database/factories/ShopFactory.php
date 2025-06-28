<?php

namespace Ijideals\ShopManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ijideals\ShopManager\Models\Shop;
use App\Models\User; // Assuming User model from the main app

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' ' . $this->faker->companySuffix,
            'description' => $this->faker->catchPhrase . '. ' . $this->faker->bs,
            'owner_id' => User::factory(), // Creates a new user as owner by default
            'is_active' => true,
            'website' => $this->faker->optional(0.7)->url,
            'phone' => $this->faker->optional(0.5)->phoneNumber,
            'address_line_1' => $this->faker->optional(0.6)->streetAddress,
            'city' => $this->faker->optional(0.6)->city,
            'postal_code' => $this->faker->optional(0.6)->postcode,
            'country_code' => $this->faker->optional(0.6)->countryCode,
            // 'settings' => null, // Default, or define some common settings
        ];
    }

    /**
     * Indicate that the shop is owned by a specific user.
     *
     * @param \App\Models\User $owner
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function ownedBy(User $owner)
    {
        return $this->state(function (array $attributes) use ($owner) {
            return [
                'owner_id' => $owner->id,
            ];
        });
    }

    /**
     * Indicate that the shop should be inactive.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
