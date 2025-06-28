<?php

namespace Ijideals\Likeable\Tests\Feature;

use Ijideals\Likeable\Tests\TestCase;
use App\Models\User; // Main app User model
use Ijideals\SocialPosts\Models\Post; // From social-posts package
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Ijideals\Likeable\Concerns\CanLike; // To apply to User for testing
use Ijideals\Likeable\Concerns\CanBeLiked; // To apply to Post for testing
use Ijideals\Likeable\Contracts\LikeableContract;


// We need a User model that uses CanLike for these tests
class TestUser extends User {
    use CanLike;
    // If User model has specific factory namespace, ensure it's used or override newFactory()
}

// We need a Post model that uses CanBeLiked and implements LikeableContract
class TestPost extends Post implements LikeableContract {
    use CanBeLiked; // This trait should provide likers() and isLikedBy()
    // If Post model has specific factory namespace, ensure it's used or override newFactory()
}


class LikeApiTest extends TestCase
{
    use RefreshDatabase;

    protected TestUser $user;
    protected TestPost $post;

    protected function setUp(): void
    {
        parent::setUp();

        // Use our TestUser and TestPost for these feature tests
        // This ensures the traits are definitely applied for the test scope
        // We'll need to tell Testbench to use this User model for authentication if it differs from App\Models\User
        // For simplicity, we are extending App\Models\User so it should still work with default auth config.

        $this->user = TestUser::factory()->create();

        // Create a post using the TestPost model
        // The createPost helper in TestCase might need adjustment or we create directly
        $author = TestUser::factory()->create(); // A different user to be the author of the post
        $this->post = TestPost::factory()->create(['user_id' => $author->id]);
                                                // Assuming PostFactory handles author correctly or user_id is sufficient
                                                // If social-posts uses polymorphic author, adjust accordingly:
                                                // 'author_id' => $author->id, 'author_type' => get_class($author)
    }

    /**
     * Override the getEnvironmentSetUp to use TestUser for auth if necessary.
     * However, since TestUser extends App\Models\User, it might just work.
     * The main thing is that the $this->user instance has the CanLike trait methods.
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        // If we had a different User model for tests that doesn't extend App\Models\User:
        // $app['config']->set('auth.providers.users.model', TestUser::class);
    }


    public function test_authenticated_user_can_like_a_post()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('posts.like', $this->post));

        $response->assertOk()
                 ->assertJson(['message' => 'Liked successfully.']);

        $this->assertDatabaseHas('likes', [
            'user_id' => $this->user->id,
            'likeable_id' => $this->post->id,
            'likeable_type' => $this->post->getMorphClass() // TestPost::class or its morph alias
        ]);
        $this->assertTrue($this->user->hasLiked($this->post));
        $this->assertTrue($this->post->isLikedBy($this->user));
        $this->assertEquals(1, $this->post->likesCount());
    }

    public function test_authenticated_user_cannot_like_a_post_twice()
    {
        Sanctum::actingAs($this->user);
        $this->user->like($this->post); // First like

        $response = $this->postJson(route('posts.like', $this->post)); // Attempt second like

        $response->assertOk(); // Liking again should not throw error, just do nothing if already liked.
        $this->assertEquals(1, $this->post->likesCount()); // Count should remain 1
    }

    public function test_authenticated_user_can_unlike_a_post()
    {
        Sanctum::actingAs($this->user);
        $this->user->like($this->post); // Like it first

        $this->assertTrue($this->user->hasLiked($this->post));
        $this->assertEquals(1, $this->post->likesCount());

        $response = $this->deleteJson(route('posts.unlike', $this->post));

        $response->assertOk()
                 ->assertJson(['message' => 'Unliked successfully.']);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $this->user->id,
            'likeable_id' => $this->post->id,
            'likeable_type' => $this->post->getMorphClass()
        ]);
        $this->assertFalse($this->user->hasLiked($this->post));
        $this->assertEquals(0, $this->post->likesCount());
    }

    public function test_unliking_a_post_not_liked_does_nothing_silently()
    {
        Sanctum::actingAs($this->user);
        // User has not liked the post yet.
        $response = $this->deleteJson(route('posts.unlike', $this->post));
        $response->assertOk();
        $this->assertEquals(0, $this->post->likesCount());
    }

    public function test_guest_cannot_like_a_post()
    {
        $response = $this->postJson(route('posts.like', $this->post));
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_unlike_a_post()
    {
        $response = $this->deleteJson(route('posts.unlike', $this->post));
        $response->assertUnauthorized();
    }

    public function test_liking_non_likeable_model_returns_error()
    {
        // This test is more conceptual as routes are specific to Post for now.
        // If we had generic routes like /likeable/{type}/{id}/like, this would be more relevant.
        // For now, this scenario is prevented by route definition.
        // If we had a non-LikeableContract model bound to a generic route, the controller should handle it.
        Sanctum::actingAs($this->user);

        // Create a dummy model that is NOT LikeableContract
        $nonLikeableModel = new class extends Model { protected $table = 'users'; }; // Use users table just for it to be a valid model
        $nonLikeableInstance = $nonLikeableModel->forceFill(['id' => 999])->syncOriginal();

        // We can't directly test the route with this non-likeable model easily
        // without more complex route/controller setup. This test is more for the controller logic.
        $controller = new \Ijideals\Likeable\Http\Controllers\LikeController();

        // Simulate request and model binding (simplified)
        $request = new \Illuminate\Http\Request();
        $response = $controller->store($request, $nonLikeableInstance);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('cannot be liked', $response->getData(true)['message']);
    }
}
