<?php

namespace Ijideals\Followable\Tests\Feature;

use Ijideals\Followable\Tests\TestCase;
// Assuming App\Models\User is your user model. Adjust if necessary.
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;


class FollowableTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected User $user3;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users for testing
        // Ensure your User model uses the Followable trait.
        // For tests, it's crucial that the User model instance within the test environment
        // correctly has the Followable trait methods.
        $this->user1 = $this->createUser(['name' => 'User One']);
        $this->user2 = $this->createUser(['name' => 'User Two']);
        $this->user3 = $this->createUser(['name' => 'User Three']);
    }

    public function test_a_user_can_follow_another_user()
    {
        $this->actingAs($this->user1);

        $this->user1->follow($this->user2);

        $this->assertTrue($this->user1->isFollowing($this->user2));
        $this->assertFalse($this->user1->isFollowing($this->user3)); // Ensure not following others

        $this->assertTrue($this->user2->followers()->where('id', $this->user1->id)->exists());
        $this->assertEquals(1, $this->user2->followers()->count());
        $this->assertEquals(1, $this->user1->followings()->count());
    }

    public function test_a_user_cannot_follow_themselves()
    {
        $this->actingAs($this->user1);
        $this->user1->follow($this->user1); // Attempt to follow self
        $this->assertFalse($this->user1->isFollowing($this->user1));
    }

    public function test_a_user_can_unfollow_another_user()
    {
        $this->actingAs($this->user1);
        $this->user1->follow($this->user2);
        $this->assertTrue($this->user1->isFollowing($this->user2));

        $this->user1->unfollow($this->user2);
        $this->assertFalse($this->user1->isFollowing($this->user2));
        $this->assertEquals(0, $this->user2->followers()->count());
        $this->assertEquals(0, $this->user1->followings()->count());
    }

    public function test_toggle_follow_works_correctly()
    {
        $this->actingAs($this->user1);

        // First toggle: follow
        $this->user1->toggleFollow($this->user2);
        $this->assertTrue($this->user1->isFollowing($this->user2));

        // Second toggle: unfollow
        $this->user1->toggleFollow($this->user2);
        $this->assertFalse($this->user1->isFollowing($this->user2));
    }

    public function test_is_following_returns_correct_status()
    {
        $this->actingAs($this->user1);
        $this->assertFalse($this->user1->isFollowing($this->user2)); // Initially not following

        $this->user1->follow($this->user2);
        $this->assertTrue($this->user1->isFollowing($this->user2)); // Now following
    }

    public function test_followers_and_followings_relationships()
    {
        // user1 follows user2
        $this->user1->follow($this->user2);
        // user3 follows user2
        $this->user3->follow($this->user2);

        // user1 follows user3
        $this->user1->follow($this->user3);

        // Check followers of user2
        $this->assertCount(2, $this->user2->followers);
        $this->assertTrue($this->user2->followers->contains($this->user1));
        $this->assertTrue($this->user2->followers->contains($this->user3));

        // Check followings of user1
        $this->assertCount(2, $this->user1->followings);
        $this->assertTrue($this->user1->followings->contains($this->user2));
        $this->assertTrue($this->user1->followings->contains($this->user3));

        // Check followers of user1 (should be 0)
        $this->assertCount(0, $this->user1->followers);

        // Check followings of user2 (should be 0)
        $this->assertCount(0, $this->user2->followings);
    }

    public function test_followers_count_accessor()
    {
        $this->user1->follow($this->user2); // user1 follows user2
        $this->user3->follow($this->user2); // user3 follows user2

        $this->assertEquals(2, $this->user2->followers_count);
        $this->assertEquals(0, $this->user1->followers_count); // user1 has no followers yet
    }

    public function test_followings_count_accessor()
    {
        $this->user1->follow($this->user2); // user1 follows user2
        $this->user1->follow($this->user3); // user1 follows user3

        $this->assertEquals(2, $this->user1->followings_count);
        $this->assertEquals(0, $this->user2->followings_count); // user2 is not following anyone yet
    }

    // --- API Endpoint Tests ---

    public function test_follow_endpoint()
    {
        $this->actingAs($this->user1);
        $response = $this->postJson(route('users.follow', $this->user2));
        $response->assertOk()
                 ->assertJson(['message' => 'Successfully followed the user.']);
        $this->assertTrue($this->user1->isFollowing($this->user2));
    }

    public function test_follow_endpoint_when_already_following()
    {
        $this->actingAs($this->user1);
        $this->user1->follow($this->user2); // Pre-condition: already following

        $response = $this->postJson(route('users.follow', $this->user2));
        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJson(['message' => 'You are already following this user.']);
    }

    public function test_follow_endpoint_cannot_follow_self()
    {
        $this->actingAs($this->user1);
        $response = $this->postJson(route('users.follow', $this->user1));
        $response->assertStatus(422)
                 ->assertJson(['message' => 'You cannot follow yourself.']);
    }

    public function test_unfollow_endpoint()
    {
        $this->actingAs($this->user1);
        $this->user1->follow($this->user2); // Pre-condition: must be following

        $response = $this->deleteJson(route('users.unfollow', $this->user2));
        $response->assertOk()
                 ->assertJson(['message' => 'Successfully unfollowed the user.']);
        $this->assertFalse($this->user1->isFollowing($this->user2));
    }

    public function test_unfollow_endpoint_when_not_following()
    {
        $this->actingAs($this->user1);
        // Pre-condition: not following
        $response = $this->deleteJson(route('users.unfollow', $this->user2));
        $response->assertStatus(422)
                 ->assertJson(['message' => 'You are not following this user.']);
    }

    public function test_toggle_follow_endpoint()
    {
        $this->actingAs($this->user1);

        // First toggle: follow
        $response = $this->postJson(route('users.togglefollow', $this->user2));
        $response->assertOk()
                 ->assertJson(['message' => 'Successfully followed the user.']);
        $this->assertTrue($this->user1->isFollowing($this->user2));

        // Second toggle: unfollow
        $response = $this->postJson(route('users.togglefollow', $this->user2));
        $response->assertOk()
                 ->assertJson(['message' => 'Successfully unfollowed the user.']);
        $this->assertFalse($this->user1->isFollowing($this->user2));
    }

    public function test_toggle_follow_endpoint_cannot_toggle_self()
    {
        $this->actingAs($this->user1);
        $response = $this->postJson(route('users.togglefollow', $this->user1));
        $response->assertStatus(422)
                 ->assertJson(['message' => 'Action not allowed on yourself.']);
    }

    public function test_is_following_endpoint()
    {
        $this->actingAs($this->user1);

        // Check when not following
        $response = $this->getJson(route('users.isfollowing', $this->user2));
        $response->assertOk()->assertJson(['is_following' => false]);

        $this->user1->follow($this->user2); // Follow the user

        // Check when following
        $response = $this->getJson(route('users.isfollowing', $this->user2));
        $response->assertOk()->assertJson(['is_following' => true]);
    }

    public function test_get_followers_endpoint()
    {
        // user2 is followed by user1 and user3
        $this->user1->follow($this->user2);
        $this->user3->follow($this->user2);

        $this->actingAs($this->user1); // Authenticated user doesn't matter for viewing followers list
        $response = $this->getJson(route('users.followers', $this->user2));

        $response->assertOk()
                 ->assertJsonCount(2) // Expecting two followers
                 ->assertJsonFragment(['id' => $this->user1->id])
                 ->assertJsonFragment(['id' => $this->user3->id]);
    }

    public function test_get_followings_endpoint()
    {
        // user1 follows user2 and user3
        $this->user1->follow($this->user2);
        $this->user1->follow($this->user3);

        $this->actingAs($this->user1); // Authenticated user doesn't matter for viewing their own followings list
        $response = $this->getJson(route('users.followings', $this->user1));

        $response->assertOk()
                 ->assertJsonCount(2) // Expecting user1 to be following two users
                 ->assertJsonFragment(['id' => $this->user2->id])
                 ->assertJsonFragment(['id' => $this->user3->id]);
    }
}
