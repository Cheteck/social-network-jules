<?php

    namespace Ijideals\NotificationSystem\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\NotificationSystem\Models\Notification;
    use App\Models\User; // Main app User model
    use Illuminate\Support\Str;

    class NotificationFactory extends Factory
    {
        protected $model = Notification::class;

        public function definition(): array
        {
            $user = User::factory()->create();
            $actor = User::factory()->create(); // User who performed an action

            // Example data for a 'new_like' notification type
            $type = 'new_like';
            $data = [
                'liker_id' => $actor->id,
                'liker_name' => $actor->name,
                'likeable_id' => $this->faker->randomNumber(), // Placeholder
                'likeable_type' => 'post', // Placeholder
                'likeable_summary' => Str::limit($this->faker->sentence, 50),
            ];

            return [
                'id' => Str::uuid()->toString(), // Model uses UUIDs
                'user_id' => $user->id, // The recipient
                'type' => $type,
                'data' => $data, // Will be JSON encoded by model mutator
                'read_at' => $this->faker->optional(0.3)->dateTimeThisMonth, // 30% chance of being read
            ];
        }

        /**
         * Indicate the notification type and customize data.
         */
        public function type(string $type, array $data = [])
        {
            return $this->state(fn (array $attributes) => [
                'type' => $type,
                'data' => array_merge($attributes['data'] ?? [], $data),
            ]);
        }

        /**
         * Indicate that the notification is unread.
         */
        public function unread()
        {
            return $this->state(fn (array $attributes) => ['read_at' => null]);
        }

        /**
         * Indicate that the notification is read.
         */
        public function read()
        {
            return $this->state(fn (array $attributes) => ['read_at' => now()]);
        }

        /**
         * For a specific user.
         */
        public function forUser(User $user)
        {
            return $this->state(fn (array $attributes) => ['user_id' => $user->id]);
        }
    }
