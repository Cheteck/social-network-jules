<?php

namespace Ijideals\NotificationSystem\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ijideals\NotificationSystem\Tests\TestCase;
use App\Models\User;
use Ijideals\SocialPosts\Models\Post;
use Ijideals\NotificationSystem\Models\Notification;
use Illuminate\Support\Facades\Event;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA; // Recipient of notifications
    protected User $userB; // Actor performing actions
    protected Post $postA; // Post by userA

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->createUser(['name' => 'User A (Recipient)']);
        $this->userB = $this->createUser(['name' => 'User B (Actor)']);
        $this->postA = $this->createPost($this->userA, ['content' => 'User A Post Content']);

        // Ensure morph map is set for tests (if not globally available)
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'post' => Post::class,
            // 'user' => User::class, // If users can be directly liked/commented etc.
        ]);
    }

    /** @test */
    public function notification_is_created_when_a_post_is_liked()
    {
        // Sanity check: userA should have 0 notifications initially
        $this->assertEquals(0, $this->userA->notifications()->count());

        // Action: userB likes userA's post
        // This relies on ijideals/likeable package firing ModelLiked event
        $this->userB->like($this->postA);

        $this->userA->refresh(); // Refresh to get new notification relation count
        $this->assertEquals(1, $this->userA->notifications()->count());
        $notification = $this->userA->notifications()->first();

        $this->assertEquals('new_like', $notification->type);
        $this->assertEquals($this->userB->id, $notification->data['liker_id']);
        $this->assertEquals($this->postA->id, $notification->data['likeable_id']);
        $this->assertEquals('post', $notification->data['likeable_type']); // Assuming 'post' is the morph alias
    }

    /** @test */
    public function notification_is_created_when_a_post_is_commented_on()
    {
        $this->assertEquals(0, $this->userA->notifications()->count());

        // Action: userB comments on userA's post
        // This relies on ijideals/commentable package firing CommentPosted event
        $this->postA->addComment('A test comment by User B', $this->userB);

        $this->userA->refresh();
        $this->assertEquals(1, $this->userA->notifications()->count());
        $notification = $this->userA->notifications()->first();

        $this->assertEquals('new_comment', $notification->type);
        $this->assertEquals($this->userB->id, $notification->data['commenter_id']);
        $this->assertEquals($this->postA->id, $notification->data['commentable_id']);
        $this->assertNotNull($notification->data['comment_id']);
    }

    /** @test */
    public function notification_is_created_when_a_user_is_followed()
    {
        $this->assertEquals(0, $this->userA->notifications()->count());

        // Action: userB follows userA
        // This relies on ijideals/followable package firing UserFollowed event
        $this->userB->follow($this->userA);

        $this->userA->refresh();
        $this->assertEquals(1, $this->userA->notifications()->count());
        $notification = $this->userA->notifications()->first();

        $this->assertEquals('new_follower', $notification->type);
        $this->assertEquals($this->userB->id, $notification->data['follower_id']);
    }

    /** @test */
    public function user_can_fetch_their_notifications_via_api()
    {
        $this->userB->like($this->postA); // Creates one notification for userA

        $this->actingAs($this->userA, 'api');
        $response = $this->getJson(route('notifications.index'));

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.type', 'new_like');
    }

    /** @test */
    public function user_can_mark_a_notification_as_read_via_api()
    {
        $this->userB->like($this->postA);
        $notification = $this->userA->notifications()->first();
        $this->assertNull($notification->read_at);

        $this->actingAs($this->userA, 'api');
        $response = $this->patchJson(route('notifications.markAsRead', ['notificationId' => $notification->id]));
        $response->assertStatus(200)
                 ->assertJson(['message' => __('notification-system::notification-system.marked_as_read_success')]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function user_can_mark_all_notifications_as_read_via_api()
    {
        $this->userB->like($this->postA);
        $this->postA->addComment('Another action', $this->userB);

        $this->assertEquals(2, $this->userA->unreadNotifications()->count());

        $this->actingAs($this->userA, 'api');
        $response = $this->postJson(route('notifications.markAllAsRead'));
        $response->assertStatus(200)
                 ->assertJson(['message' => __('notification-system::notification-system.all_marked_as_read_success')]);
        $this->assertEquals(0, $this->userA->unreadNotifications()->count());
    }

    /** @test */
    public function user_can_get_unread_notifications_count_via_api()
    {
        $this->userB->like($this->postA); // 1 unread

        $this->actingAs($this->userA, 'api');
        $response = $this->getJson(route('notifications.unread.count'));
        $response->assertStatus(200)
                 ->assertJson(['unread_count' => 1]);

        // Mark it as read and check again
        $this->userA->notifications()->first()->markAsRead();
        $response = $this->getJson(route('notifications.unread.count'));
        $response->assertStatus(200)
                 ->assertJson(['unread_count' => 0]);
    }

    /** @test */
    public function no_notification_for_liking_own_post()
    {
        $this->userA->like($this->postA); // userA likes their own post
        $this->assertEquals(0, $this->userA->notifications()->count());
    }

    /** @test */
    public function no_notification_for_commenting_on_own_post()
    {
        $this->postA->addComment('Self comment', $this->userA);
        $this->assertEquals(0, $this->userA->notifications()->count());
    }

    /** @test */
    public function api_messages_are_translated_to_french()
    {
        $this->actingAs($this->userA, 'api');
        app()->setLocale('fr');

        // Create a notification
        $this->userB->like($this->postA);
        $notification = $this->userA->notifications()->first();
        $this->assertNotNull($notification);

        // Test markAsRead
        $responseMarkAsRead = $this->withHeaders(['Accept-Language' => 'fr'])
                                   ->patchJson(route('notifications.markAsRead', ['notificationId' => $notification->id]));
        $responseMarkAsRead->assertStatus(200)
                           ->assertJson(['message' => 'Notification marquée comme lue.']);

        // Make it unread again for next test part
        $notification->markAsUnread();

        // Test markAllAsRead (ensure there's at least one unread)
        $this->userB->follow($this->userA); // Creates another notification
        $this->assertGreaterThanOrEqual(1, $this->userA->unreadNotifications()->count());
        $responseMarkAll = $this->withHeaders(['Accept-Language' => 'fr'])
                                ->postJson(route('notifications.markAllAsRead'));
        $responseMarkAll->assertStatus(200)
                        ->assertJson(['message' => 'Toutes les notifications non lues ont été marquées comme lues.']);

        // Create one more notification to test deletion
        $this->userB->like($this->postA); // This will create a new one as previous was marked read
        $notificationToDelete = $this->userA->notifications()->latest()->first();

        // Test delete
        $responseDelete = $this->withHeaders(['Accept-Language' => 'fr'])
                               ->deleteJson(route('notifications.destroy', ['notificationId' => $notificationToDelete->id]));
        $responseDelete->assertStatus(200)
                       ->assertJson(['message' => 'Notification supprimée avec succès.']);

        // Test notification not found
        $responseNotFound = $this->withHeaders(['Accept-Language' => 'fr'])
                                 ->deleteJson(route('notifications.destroy', ['notificationId' => $notificationToDelete->id]));
        $responseNotFound->assertStatus(404)
                         ->assertJson(['message' => 'Notification non trouvée.']);

        app()->setLocale(config('app.fallback_locale', 'en')); // Reset locale
    }
}
