<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User; // Modèle User de l'application principale
use Ijideals\SocialPosts\Models\Post; // Modèle Post de notre package - Corrected Namespace

class SocialPostsApiTest extends TestCase
{
    use RefreshDatabase; // Rafraîchit la base de données pour chaque test

    /** @var \App\Models\User */
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Créer un utilisateur pour les tests
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_create_post(): void
    {
        $postData = [
            'content' => 'This is a test post content.',
        ];

        // Agir en tant qu'utilisateur authentifié et faire la requête POST
        $response = $this->actingAs($this->user, 'sanctum') // Utiliser 'sanctum' pour l'authentification API
                         ->postJson(route('socialposts.posts.store'), $postData);

        $response->dump(); // Dump the response to see the error details

        $response->assertStatus(201) // Vérifier le code de statut HTTP 201 Created
                 ->assertJsonStructure([ // Vérifier la structure de la réponse JSON
                     'id',
                     'content',
                     'author_id',
                     'author_type',
                     'created_at',
                     'updated_at',
                     'author' => [
                         'id',
                         'name',
                         'email',
                         // autres champs de User si nécessaire
                     ]
                 ])
                 ->assertJson([ // Vérifier les valeurs spécifiques
                     'content' => $postData['content'],
                     'author_id' => $this->user->id,
                     'author_type' => User::class,
                     'author' => [
                        'id' => $this->user->id,
                     ]
                 ]);

        // Vérifier que le post existe dans la base de données
        $this->assertDatabaseHas('posts', [
            'author_id' => $this->user->id,
            'author_type' => User::class,
            'content' => $postData['content'],
        ]);
    }

    public function test_authenticated_user_can_list_posts(): void
    {
        // Créer quelques posts pour cet utilisateur
        Post::factory()->count(3)->for($this->user, 'author')->create();

        // Créer un post pour un autre utilisateur pour s'assurer qu'on ne récupère que les bons
        $otherUser = User::factory()->create();
        Post::factory()->for($otherUser, 'author')->create(['content' => 'Other user post']);


        // Agir en tant qu'utilisateur authentifié et faire la requête GET
        $response = $this->actingAs($this->user, 'sanctum')
                         ->getJson(route('socialposts.posts.index'));

        $response->assertStatus(200)
                 ->assertJsonCount(4) // 3 de $this->user + 1 de $otherUser (l'index actuel retourne tout)
                 ->assertJsonStructure([
                     '*' => [ // Collection de posts
                         'id',
                         'content',
                          'author_id',
                          'author_type',
                         'author' => ['id', 'name']
                     ]
                 ]);

        // On pourrait affiner ce test pour vérifier que les posts de $this->user sont bien là.
        // Par exemple, en vérifiant le contenu d'un des posts.
        // L'implémentation actuelle de PostController@index retourne tous les posts.
        // Si l'on voulait un fil d'actualité, il faudrait filtrer.
    }

    public function test_unauthenticated_user_cannot_create_post(): void
    {
        $postData = [
            'content' => 'Unauthenticated post attempt.',
        ];

        $response = $this->postJson(route('socialposts.posts.store'), $postData);

        $response->assertStatus(401); // Unauthorized
    }

    public function test_authenticated_user_can_update_own_post(): void
    {
        $post = Post::factory()->for($this->user, 'author')->create(['content' => 'Original content']);
        $updatedData = ['content' => 'Updated test post content.'];

        $response = $this->actingAs($this->user, 'sanctum')
                         ->putJson(route('socialposts.posts.update', $post), $updatedData);

        $response->assertStatus(200)
                 ->assertJson(['content' => $updatedData['content']]); // Correction ici

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => $updatedData['content'],
        ]);
    }

    public function test_authenticated_user_cannot_update_others_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->for($otherUser, 'author')->create(['content' => 'Other user original content']);
        $updatedData = ['content' => 'Attempt to update others post.'];

        $response = $this->actingAs($this->user, 'sanctum')
                         ->putJson(route('socialposts.posts.update', $post), $updatedData);

        $response->assertStatus(403); // Forbidden
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Other user original content', // Le contenu ne doit pas avoir changé
        ]);
    }

    public function test_authenticated_user_can_delete_own_post(): void
    {
        $post = Post::factory()->for($this->user, 'author')->create();

        $response = $this->actingAs($this->user, 'sanctum')
                         ->deleteJson(route('socialposts.posts.destroy', $post));

        $response->assertStatus(204); // No Content
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_authenticated_user_cannot_delete_others_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->for($otherUser, 'author')->create();

        $response = $this->actingAs($this->user, 'sanctum')
                         ->deleteJson(route('socialposts.posts.destroy', $post));

        $response->assertStatus(403); // Forbidden
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}
