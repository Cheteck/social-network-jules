<?php

namespace Ijideals\Likeable\Tests\Feature;

use Ijideals\Likeable\Tests\TestCase;
use Ijideals\Likeable\Tests\User;
use Ijideals\Likeable\Tests\Post;
use Ijideals\Likeable\Tests\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Ijideals\Likeable\Events\Liked;
use Ijideals\Likeable\Events\Unliked;

class LikeableTest extends TestCase
{
    // We don't need RefreshDatabase here if TestCase handles schema creation correctly for each test run.
    // However, Orchestra Testbench typically handles this. If issues arise, enable it.
    // use RefreshDatabase;

    protected User $user;
    protected Post $post;
    protected Article $article;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake(); // Fake events for assertions

        $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'api_token' => 'test_token']);
        $this->post = Post::create(['title' => 'Test Post', 'user_id' => $this->user->id]);
        $this->article = Article::create(['name' => 'Test Article']);
    }

    /** @test */
    public function a_user_can_like_a_post()
    {
        $this->assertFalse($this->user->hasLiked($this->post));
        $this->assertEquals(0, $this->post->likesCount);

        $this->user->like($this->post);

        $this->assertTrue($this->user->hasLiked($this->post));
        $this->assertEquals(1, $this->post->fresh()->likesCount);
        $this->assertCount(1, $this->post->likes);
        Event::assertDispatched(Liked::class, function ($event) {
            return $event->likeable->is($this->post) && $event->liker->is($this->user);
        });
    }

    /** @test */
    public function a_user_can_unlike_a_post()
    {
        $this->user->like($this->post);
        $this->assertTrue($this->user->hasLiked($this->post));
        $this->assertEquals(1, $this->post->fresh()->likesCount);

        $this->user->unlike($this->post);

        $this->assertFalse($this->user->hasLiked($this->post));
        $this->assertEquals(0, $this->post->fresh()->likesCount);
        Event::assertDispatched(Unliked::class, function ($event) {
            return $event->likeable->is($this->post) && $event->liker->is($this->user);
        });
    }

    /** @test */
    public function a_user_can_toggle_like_on_a_post()
    {
        $this->user->toggleLike($this->post);
        $this->assertTrue($this->user->hasLiked($this->post));
        $this->assertEquals(1, $this->post->fresh()->likesCount);

        $this->user->toggleLike($this->post);
        $this->assertFalse($this->user->hasLiked($this->post));
        $this->assertEquals(0, $this->post->fresh()->likesCount);
    }

    /** @test */
    public function a_user_can_like_an_article()
    {
        $this->assertFalse($this->user->hasLiked($this->article));
        $this->assertEquals(0, $this->article->likesCount);

        $this->user->like($this->article);

        $this->assertTrue($this->user->hasLiked($this->article));
        $this->assertEquals(1, $this->article->fresh()->likesCount);
        Event::assertDispatched(Liked::class);
    }

    /** @test */
    public function multiple_users_can_like_a_post()
    {
        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@example.com']);

        $this->user->like($this->post);
        $user2->like($this->post);

        $this->assertEquals(2, $this->post->fresh()->likesCount);
        $this->assertTrue($this->user->hasLiked($this->post));
        $this->assertTrue($user2->hasLiked($this->post));
    }

    /** @test */
    public function liking_a_post_multiple_times_by_same_user_has_no_effect()
    {
        $this->user->like($this->post);
        $this->user->like($this->post); // Try liking again

        $this->assertEquals(1, $this->post->fresh()->likesCount);
        Event::assertDispatchedTimes(Liked::class, 1); // Should only dispatch once
    }

    /** @test */
    public function unliking_a_post_not_liked_has_no_effect()
    {
        $this->user->unlike($this->post); // Try unliking

        $this->assertEquals(0, $this->post->fresh()->likesCount);
        Event::assertNotDispatched(Unliked::class);
    }

    /** @test */
    public function it_retrieves_posts_liked_by_a_user()
    {
        $post2 = Post::create(['title' => 'Another Post', 'user_id' => $this->user->id]);
        $this->user->like($this->post);
        $this->user->like($post2);
        $this->user->like($this->article); // Like a different type

        $likedPosts = $this->user->getLikedModels(Post::class);

        $this->assertCount(2, $likedPosts);
        $this->assertTrue($likedPosts->contains($this->post));
        $this->assertTrue($likedPosts->contains($post2));
        $this->assertFalse($likedPosts->contains($this->article)); // Ensure it's not Post
    }

    /** @test */
    public function it_retrieves_articles_liked_by_a_user()
    {
        $article2 = Article::create(['name' => 'Another Article']);
        $this->user->like($this->article);
        $this->user->like($article2);
        $this->user->like($this->post); // Like a different type

        $likedArticles = $this->user->getLikedModels(Article::class);

        $this->assertCount(2, $likedArticles);
        $this->assertTrue($likedArticles->contains($this->article));
        $this->assertTrue($likedArticles->contains($article2));
    }

    /** @test */
    public function scope_where_liked_by_filters_correctly()
    {
        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@example.com']);
        $post2 = Post::create(['title' => 'Post Two by User1', 'user_id' => $this->user->id]);
        $post3 = Post::create(['title' => 'Post Three by User2', 'user_id' => $user2->id]);

        $this->user->like($this->post);
        $this->user->like($post2);
        $user2->like($post3);
        $user2->like($this->post); // user2 also likes post1

        $postsLikedByUser1 = Post::whereLikedBy($this->user)->get();
        $this->assertCount(2, $postsLikedByUser1);
        $this->assertTrue($postsLikedByUser1->contains($this->post));
        $this->assertTrue($postsLikedByUser1->contains($post2));
        $this->assertFalse($postsLikedByUser1->contains($post3));

        $postsLikedByUser2 = Post::whereLikedBy($user2)->get();
        $this->assertCount(2, $postsLikedByUser2); // post1 and post3
        $this->assertTrue($postsLikedByUser2->contains($this->post));
        $this->assertTrue($postsLikedByUser2->contains($post3));
    }

    /** @test */
    public function deleting_a_likeable_model_also_deletes_its_likes()
    {
        $this->user->like($this->post);
        $this->assertEquals(1, $this->post->likes()->count());
        $this->assertEquals(1, \Ijideals\Likeable\Models\Like::count());

        $this->post->delete();

        $this->assertEquals(0, \Ijideals\Likeable\Models\Like::count());
    }

    /** @test */
    public function deleting_a_user_also_deletes_their_likes()
    {
        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@example.com']);
        $this->user->like($this->post);
        $user2->like($this->post);

        $this->assertEquals(2, $this->post->fresh()->likesCount);
        $this->assertEquals(2, \Ijideals\Likeable\Models\Like::count());

        $this->user->delete(); // This should cascade delete likes by this user

        $this->assertEquals(1, \Ijideals\Likeable\Models\Like::count());
        $this->assertEquals(1, $this->post->fresh()->likesCount);
        $this->assertFalse($this->post->isLikedBy($this->user)); // Check with original user object
        $this->assertTrue($this->post->isLikedBy($user2));
    }

    // --- API Tests ---

    /** @test */
    public function api_can_like_a_post()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->user->api_token])
                         ->postJson(route('likeable.like.test', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Successfully liked.',
                     'likes_count' => 1,
                 ]);
        $this->assertTrue($this->user->hasLiked($this->post));
        Event::assertDispatched(Liked::class);
    }

    /** @test */
    public function api_cannot_like_a_post_twice()
    {
        $this->user->like($this->post); // Like it first

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->user->api_token])
                         ->postJson(route('likeable.like.test', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));

        $response->assertStatus(409) // Conflict
                 ->assertJson(['message' => 'Already liked.']);
        $this->assertEquals(1, $this->post->fresh()->likesCount);
    }

    /** @test */
    public function api_can_unlike_a_post()
    {
        $this->user->like($this->post); // Like it first

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->user->api_token])
                         ->deleteJson(route('likeable.unlike.test', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Successfully unliked.',
                     'likes_count' => 0,
                 ]);
        $this->assertFalse($this->user->hasLiked($this->post));
        Event::assertDispatched(Unliked::class);
    }

    /** @test */
    public function api_cannot_unlike_a_post_not_liked()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->user->api_token])
                         ->deleteJson(route('likeable.unlike.test', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));

        $response->assertStatus(409) // Conflict
                 ->assertJson(['message' => 'Not previously liked.']);
        $this->assertEquals(0, $this->post->fresh()->likesCount);
    }

    /** @test */
    public function api_returns_404_for_non_existent_likeable()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->user->api_token])
                         ->postJson(route('likeable.like.test', ['likeable_type' => 'post', 'likeable_id' => 999]));
        $response->assertStatus(404);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->user->api_token])
                         ->deleteJson(route('likeable.unlike.test', ['likeable_type' => 'post', 'likeable_id' => 999]));
        $response->assertStatus(404);
    }

    /** @test */
    public function api_returns_401_for_unauthenticated_user()
    {
        $response = $this->postJson(route('likeable.like.test', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $response->assertStatus(401);

        $response = $this->deleteJson(route('likeable.unlike.test', ['likeable_type' => 'post', 'likeable_id' => $this->post->id]));
        $response->assertStatus(401);
    }

    /** @test */
    public function api_returns_404_for_invalid_likeable_type()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->user->api_token])
                         ->postJson(route('likeable.like.test', ['likeable_type' => 'non_existent_type', 'likeable_id' => $this->post->id]));
        $response->assertStatus(404)
                 ->assertJson(['message' => 'Likeable entity not found.']);
    }
}
