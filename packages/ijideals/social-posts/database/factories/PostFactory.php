<?php

namespace Ijideals\SocialPosts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ijideals\SocialPosts\Models\Post; // Corrected
use App\Models\User; // Pour associer à un utilisateur existant

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Ijideals\SocialPosts\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Ijideals\SocialPosts\Models\Post>
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Par défaut, l'auteur est un nouvel utilisateur.
        // On pourrait rendre cela plus flexible si nécessaire.
        $author = User::factory()->create();

        return [
            'author_id' => $author->id,
            'author_type' => get_class($author), // Ou User::class directement si toujours User par défaut
            'content' => $this->faker->paragraph,
        ];
    }
}
