<?php

    namespace Ijideals\UserSettings\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\UserSettings\Models\UserSetting;
    use App\Models\User; // Main app User model
    use Illuminate\Support\Arr;

    class UserSettingFactory extends Factory
    {
        protected $model = UserSetting::class;

        public function definition(): array
        {
            $user = User::factory()->create();

            // Get a random setting key and its default value from config
            $defaultSettings = config('user-settings.defaults', []);
            $dottedSettings = Arr::dot($defaultSettings);
            $randomKey = $this->faker->randomElement(array_keys($dottedSettings));
            $defaultValue = $dottedSettings[$randomKey];

            // Determine a value for the setting, possibly different from default
            $value = $defaultValue;
            if (is_bool($defaultValue)) {
                $value = $this->faker->boolean;
            } elseif (is_numeric($defaultValue)) {
                $value = $this->faker->numberBetween(0, 100);
            } elseif (is_string($defaultValue)) {
                // Could be an enum-like string, pick from a predefined list or keep default
                // For simplicity, let's sometimes change it if it's a generic string.
                if (strlen($defaultValue) < 20 && $this->faker->boolean(30)) { // 30% chance to change short strings
                    $value = $this->faker->word;
                }
            }
            // Arrays/objects could be faked too if needed

            return [
                'user_id' => $user->id,
                'key' => $randomKey,
                'value' => $value, // The model's mutator will handle encoding (e.g., for booleans to '1'/'0')
            ];
        }

        /**
         * Define a specific setting key and value.
         */
        public function withSetting(string $key, $value)
        {
            return $this->state(function (array $attributes) use ($key, $value) {
                return [
                    'key' => $key,
                    'value' => $value,
                ];
            });
        }

        /**
         * For a specific user.
         */
        public function forUser(User $user)
        {
            return $this->state(function (array $attributes) use ($user) {
                return [
                    'user_id' => $user->id,
                ];
            });
        }
    }
