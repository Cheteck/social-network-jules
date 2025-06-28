<?php

namespace Ijideals\Likeable\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Ijideals\Likeable\Tests\TestCase;
use App\Models\User; // Main app User model
use Ijideals\SocialPosts\Models\Post; // Example Likeable model
use Ijideals\Likeable\Models\Like;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;


class LikeableTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $anotherUser;
    protected $post;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually register the morph map for tests if not already done by the app's service providers
        // This is crucial if your package relies on morph maps defined outside of itself.
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'post' => Post::class,
            // Add other likeable models if they are used in tests
        ]);

        // It's good practice to also ensure config for the package is loaded,
        // especially if the package's own service provider isn't fully run in tests.
        // config(['likeable.user_model' => User::class]);
        // config(['likeable.like_model' => Like::class]);
        // config(['likeable.table_name' => 'likes']);


        $this->user = User::factory()->create();
        $this->anotherUser = User::factory()->create();

        // Ensure Post model uses CanBeLiked and User uses CanLike
        // This is implicitly tested by the operations below.

        // Create a post using the social-posts package factory if available, or a simple one
        if (class_exists(\Ijideals\SocialPosts\Database\Factories\PostFactory::class)) {
            $this->post = \Ijideals\SocialPosts\Database\Factories\PostFactory::new()->create(['author_id' => $this->user->id, 'author_type' => get_class($this->user)]);
        } else {
            // Fallback if the specific factory isn't found or correctly namespaced for tests
            $this->post = Post::create([
                'content' => $this->faker->paragraph,
                'author_id' => $this->user->id,
                'author_type' => get_class($this->user) // Assuming Post model has author_type for morphTo
            ]);
        }

        // Event::fake();
        // Notification::fake();
    }

    /** @test */
    public function a_user_can_like_a_post()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson(route('likeable.like', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => __('likeable::likeable.successfully_liked'),
                     'likes_count' => 1
                 ]);

        $this->assertCount(1, $this->post->likes);
        $this->assertTrue($this->post->isLikedBy($this->user));
        $this->assertTrue($this->user->hasLiked($this->post));
        $this->assertEquals(1, $this->post->likes_count);
    }

    /** @test */
    public function a_user_cannot_like_a_post_twice()
    {
        $this->actingAs($this->user, 'api');
        $this->user->like($this->post); // First like

        $response = $this->postJson(route('likeable.like', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));

        $response->assertStatus(409) // Conflict
                 ->assertJson(['message' => __('likeable::likeable.already_liked')]);

        $this->assertCount(1, $this->post->refresh()->likes);
        $this->assertEquals(1, $this->post->likes_count);
    }

    /** @test */
    public function a_user_can_unlike_a_liked_post()
    {
        $this->actingAs($this->user, 'api');
        $this->user->like($this->post);

        $this->assertTrue($this->post->isLikedBy($this->user));
        $this->assertEquals(1, $this->post->likes_count);

        $response = $this->deleteJson(route('likeable.unlike', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => __('likeable::likeable.successfully_unliked'),
                     'likes_count' => 0
                 ]);

        $this->assertCount(0, $this->post->refresh()->likes);
        $this->assertFalse($this->post->isLikedBy($this->user));
        $this->assertFalse($this->user->hasLiked($this->post));
        $this->assertEquals(0, $this->post->likes_count);
    }

    /** @test */
    public function a_user_cannot_unlike_a_post_they_havent_liked()
    {
        $this->actingAs($this->user, 'api');
        // Post is not liked by $this->user

        $response = $this->deleteJson(route('likeable.unlike', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $response->assertStatus(409) // Conflict or 404 depending on controller logic for "not found to unlike"
                 ->assertJson(['message' => __('likeable::likeable.not_liked_yet')]);


        $this->assertCount(0, $this->post->refresh()->likes);
    }

    /** @test */
    public function a_user_can_toggle_like_on_a_post()
    {
        $this->actingAs($this->user, 'api');

        // First toggle: Like
        $responseLike = $this->postJson(route('likeable.toggle', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $responseLike->assertStatus(200) // Controller returns 200 for toggle success
                     ->assertJson([
                         'message' => __('likeable::likeable.toggled_liked'),
                         'status' => 'liked',
                         'likes_count' => 1
                     ]);
        $this->assertTrue($this->post->refresh()->isLikedBy($this->user));
        $this->assertEquals(1, $this->post->likes_count);

        // Second toggle: Unlike
        $responseUnlike = $this->postJson(route('likeable.toggle', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $responseUnlike->assertStatus(200)
                       ->assertJson([
                           'message' => __('likeable::likeable.toggled_unliked'),
                           'status' => 'unliked',
                           'likes_count' => 0
                       ]);
        $this->assertFalse($this->post->refresh()->isLikedBy($this->user));
        $this->assertEquals(0, $this->post->likes_count);
    }

    /** @test */
    public function unauthenticated_user_cannot_like_a_post()
    {
        // Not acting as any user
        $response = $this->postJson(route('likeable.like', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $response->assertStatus(401); // Unauthorized
    }

    /** @test */
    public function likes_are_deleted_when_likeable_model_is_deleted()
    {
        $this->user->like($this->post);
        $this->anotherUser->like($this->post);

        $this->assertCount(2, $this->post->likes);
        $likeIds = $this->post->likes->pluck('id');

        $this->post->delete(); // This should trigger bootCanBeLiked via deleting event

        $this->assertDatabaseMissing('posts', ['id' => $this->post->id]);
        foreach ($likeIds as $likeId) {
            $this->assertDatabaseMissing(config('likeable.table_name', 'likes'), ['id' => $likeId]);
        }
    }

    /** @test */
    public function user_can_get_liked_items()
    {
        $post2 = Post::factory()->create(['author_id' => $this->user->id, 'author_type' => get_class($this->user)]);

        $this->user->like($this->post);
        $this->user->like($post2);

        // At this point, the user model directly may not have a generic `likedItems()` method
        // that works for the API. The trait `CanLike` has `getLikedItems(ClassName::class)`.
        // This test will focus on the trait's functionality.

        $likedPosts = $this->user->getLikedItems(Post::class);

        $this->assertCount(2, $likedPosts);
        $this->assertTrue($likedPosts->contains($this->post));
        $this->assertTrue($likedPosts->contains($post2));
    }

    /** @test */
    public function likes_count_is_correctly_retrieved()
    {
        $this->user->like($this->post);
        $this->anotherUser->like($this->post);

        $this->assertEquals(2, $this->post->likes_count);
        $this->assertEquals(2, $this->post->refresh()->likes()->count());

        // Test with eager loading if you have routes that do this
        // $postWithCount = Post::withCount('likes')->find($this->post->id);
        // $this->assertEquals(2, $postWithCount->likes_count);
    }

    /** @test */
    public function non_likeable_model_or_type_returns_error()
    {
        $this->actingAs($this->user, 'api');

        // Assuming 'nonexistent_type' is not in morphMap
        $response = $this->postJson(route('likeable.like', ['likeable_type' => 'nonexistent_type', 'likeable_id' => 1]));
        $response->assertStatus(404)
                 ->assertJson(['message' => __('likeable::likeable.entity_not_found')]);


        // Assuming a model that exists but doesn't use CanBeLiked trait.
        // For this, you'd need to create such a model and register it in morphMap for the test.
        // e.g., \Illuminate\Database\Eloquent\Relations\Relation::morphMap(['unlikable' => UnlikableModel::class]);
        // $unlikable = UnlikableModel::factory()->create();
        // $response = $this->postJson(route('likeable.like', ['likeable_type' => 'unlikable', 'likeable_id' => $unlikable->id]));
        // $response->assertStatus(404)->assertJson(['message' => __('likeable::likeable.entity_not_found')]);
    }

    /** @test */
    public function api_messages_are_translated_to_french()
    {
        $this->actingAs($this->user, 'api');
        app()->setLocale('fr'); // Set locale for this test execution context

        // Test a "successfully liked" message
        $responseLike = $this->withHeaders(['Accept-Language' => 'fr'])
                             ->postJson(route('likeable.like', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $responseLike->assertStatus(201)
                     ->assertJson(['message' => 'Aimé avec succès.']); // Exact French translation

        // Test an "already liked" message
        $responseAlreadyLiked = $this->withHeaders(['Accept-Language' => 'fr'])
                                     ->postJson(route('likeable.like', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $responseAlreadyLiked->assertStatus(409)
                             ->assertJson(['message' => 'Déjà aimé.']); // Exact French translation

        // Test a "successfully unliked" message
        $responseUnlike = $this->withHeaders(['Accept-Language' => 'fr'])
                               ->deleteJson(route('likeable.unlike', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $responseUnlike->assertStatus(200)
                       ->assertJson(['message' => 'N\'est plus aimé avec succès.']);

        // Test a "not liked yet" message
        $responseNotLiked = $this->withHeaders(['Accept-Language' => 'fr'])
                                 ->deleteJson(route('likeable.unlike', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $responseNotLiked->assertStatus(409)
                         ->assertJson(['message' => 'Pas encore aimé.']);

        // Reset locale for other tests
        app()->setLocale(config('app.fallback_locale', 'en'));
    }
}
