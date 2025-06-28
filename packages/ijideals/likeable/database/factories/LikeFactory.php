<?php

    namespace Ijideals\Likeable\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\Likeable\Models\Like;
    use App\Models\User; // Main app User model
    use Ijideals\SocialPosts\Models\Post; // Example likeable model

    class LikeFactory extends Factory
    {
        protected $model = Like::class;

        public function definition(): array
        {
            // Default to liking a Post created by a User
            $user = User::factory()->create();
            $likeable = Post::factory()->create(['author_id' => User::factory()->create()->id, 'author_type' => User::class]);

            return [
                'user_id' => $user->id,
                'likeable_id' => $likeable->id,
                'likeable_type' => $likeable->getMorphClass(), // Uses morph map if defined
            ];
        }

        /**
         * Indicate that the like is for a specific likeable model.
         *
         * @param \Illuminate\Database\Eloquent\Model $likeable
         * @return \Illuminate\Database\Eloquent\Factories\Factory
         */
        public function forLikeable(\Illuminate\Database\Eloquent\Model $likeable)
        {
            return $this->state(function (array $attributes) use ($likeable) {
                return [
                    'likeable_id' => $likeable->id,
                    'likeable_type' => $likeable->getMorphClass(),
                ];
            });
        }

        /**
         * Indicate that the like is by a specific user.
         *
         * @param \App\Models\User $user
         * @return \Illuminate\Database\Eloquent\Factories\Factory
         */
        public function byUser(User $user)
        {
            return $this->state(function (array $attributes) use ($user) {
                return [
                    'user_id' => $user->id,
                ];
            });
        }
    }
