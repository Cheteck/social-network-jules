<?php

    namespace Ijideals\Commentable\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\Commentable\Models\Comment;
    use App\Models\User; // Main app User model
    use Ijideals\SocialPosts\Models\Post; // Example commentable model

    class CommentFactory extends Factory
    {
        protected $model = Comment::class;

        public function definition(): array
        {
            $commenter = User::factory()->create();
            $commentable = Post::factory()->create(['author_id' => User::factory()->create()->id, 'author_type' => User::class]);

            return [
                'user_id' => $commenter->id, // commenter_id or author_id depending on your model
                'commentable_id' => $commentable->id,
                'commentable_type' => $commentable->getMorphClass(),
                'content' => $this->faker->paragraph,
                'parent_id' => null, // Default to top-level comment
                // 'approved_at' => now(), // If you have an approval system
            ];
        }

        /**
         * Indicate that the comment is for a specific commentable model.
         */
        public function forCommentable(\Illuminate\Database\Eloquent\Model $commentable)
        {
            return $this->state(fn (array $attributes) => [
                'commentable_id' => $commentable->id,
                'commentable_type' => $commentable->getMorphClass(),
            ]);
        }

        /**
         * Indicate that the comment is by a specific user.
         */
        public function byUser(User $user)
        {
            return $this->state(fn (array $attributes) => ['user_id' => $user->id]);
        }

        /**
         * Indicate that the comment is a reply to another comment.
         */
        public function asReplyTo(Comment $parentComment)
        {
            return $this->state(fn (array $attributes) => [
                'parent_id' => $parentComment->id,
                // Ensure the reply is on the same commentable as the parent
                'commentable_id' => $parentComment->commentable_id,
                'commentable_type' => $parentComment->commentable_type,
            ]);
        }

        /**
         * Indicate that the comment is approved.
         */
        public function approved()
        {
            return $this->state(fn (array $attributes) => ['approved_at' => now()]);
        }
    }
