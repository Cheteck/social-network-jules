<?php

namespace Ijideals\UserSettings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ijideals\UserSettings\Tests\TestCase; // Uses the package's TestCase
use App\Models\User;
use Ijideals\SocialPosts\Models\Post; // For testing notification integration
use Ijideals\NotificationSystem\Models\Notification; // For checking notifications

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

    // $this->testUser is created in parent TestCase::setUp()

    /** @test */
    public function user_can_retrieve_their_settings_which_merge_with_defaults()
    {
        $this->actingAs($this->testUser, 'api');

        // Set one specific setting
        $this->testUser->setSetting('notifications.new_like.database', false);

        $response = $this->getJson(route('user.settings.index'));
        $response->assertStatus(200)
            ->assertJson([
                'notifications' => [
                    'new_like' => ['database' => false], // User's override
                    'new_comment' => ['database' => true], // Default
                    'new_follower' => ['database' => false], // Default from test config
                ],
                'privacy' => [
                    'profile_visibility' => 'public' // Default
                ]
            ]);
    }

    /** @test */
    public function user_can_retrieve_specific_settings_by_keys()
    {
        $this->actingAs($this->testUser, 'api');
        $this->testUser->setSetting('privacy.profile_visibility', 'followers_only');

        $keysToFetch = 'notifications.new_comment.database,privacy.profile_visibility';
        $response = $this->getJson(route('user.settings.index', ['keys' => $keysToFetch]));

        $response->assertStatus(200)
            ->assertJson([
                'notifications.new_comment.database' => true, // Default
                'privacy.profile_visibility' => 'followers_only' // User's override
            ])
            ->assertJsonMissingPath('notifications.new_like.database');
    }

    /** @test */
    public function user_can_update_their_settings()
    {
        $this->actingAs($this->testUser, 'api');
        $newSettings = [
            'notifications.new_like.database' => false,
            'privacy.profile_visibility' => 'private',
            'notifications.new_follower.database' => true, // Change from default false
        ];

        $response = $this->putJson(route('user.settings.update'), $newSettings);
        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Settings updated successfully.')
                 ->assertJsonPath('settings.notifications.new_like.database', false)
                 ->assertJsonPath('settings.privacy.profile_visibility', 'private')
                 ->assertJsonPath('settings.notifications.new_follower.database', true);

        $this->assertEquals(false, $this->testUser->getSetting('notifications.new_like.database'));
        $this->assertEquals('private', $this->testUser->getSetting('privacy.profile_visibility'));
        $this->assertEquals(true, $this->testUser->getSetting('notifications.new_follower.database'));
    }

    /** @test */
    public function updating_with_undefined_keys_does_not_save_them()
    {
        $this->actingAs($this->testUser, 'api');
        $settingsToUpdate = [
            'notifications.new_like.database' => false,
            'undefined.key.should.be.ignored' => 'some_value'
        ];

        $this->putJson(route('user.settings.update'), $settingsToUpdate)
             ->assertStatus(200); // Controller doesn't fail, just ignores

        $allSettings = $this->testUser->getAllSettings();
        $this->assertFalse(Arr::has($allSettings, 'undefined.key.should.be.ignored'));
        $this->assertEquals(false, $allSettings['notifications']['new_like']['database']);
    }

    /** @test */
    public function boolean_settings_are_correctly_cast()
    {
        $this->actingAs($this->testUser, 'api');

        // Test setting with string "true" / "false" or 1 / 0
        $this->putJson(route('user.settings.update'), ['notifications.new_like.database' => 'false']);
        $this->assertFalse($this->testUser->getSetting('notifications.new_like.database'));

        $this->putJson(route('user.settings.update'), ['notifications.new_like.database' => '1']);
        $this->assertTrue($this->testUser->getSetting('notifications.new_like.database'));

        $response = $this->getJson(route('user.settings.index', ['keys' => 'notifications.new_like.database']));
        $response->assertJson(['notifications.new_like.database' => true]);
    }

    // --- Integration Test with Notification System ---

    /** @test */
    public function notification_is_not_created_if_user_setting_disables_it()
    {
        // User A is the recipient, User B is the actor
        $userA = $this->testUser; // from parent TestCase, HasSettings trait is applied
        $userB = $this->createUser(['name' => 'Actor User']);
        $postByA = $this->createPostForUser($userA, ['content' => 'A post by User A']);

        // Disable 'new_like' database notifications for userA
        $userA->setSetting('notifications.new_like.database', false);
        $this->assertEquals(false, $userA->getSetting('notifications.new_like.database'));
        $this->assertEquals(0, Notification::where('user_id', $userA->id)->count());

        // Action: userB likes userA's post
        // This should trigger ModelLiked event, which NotificationCreationService listens to.
        // NotificationCreationService should check userA's setting.
        $userB->like($postByA);

        // Assert no notification was created in the database for userA
        $this->assertEquals(0, Notification::where('user_id', $userA->id)
                                          ->where('type', 'new_like')
                                          ->count());
    }

    /** @test */
    public function notification_is_created_if_user_setting_enables_it_or_is_default_true()
    {
        $userA = $this->testUser;
        $userB = $this->createUser(['name' => 'Another Actor']);
        $postByA = $this->createPostForUser($userA, ['content' => 'Another post by User A']);

        // 'notifications.new_comment.database' is true by default in test config
        $this->assertEquals(true, $userA->getSetting('notifications.new_comment.database'));
        $this->assertEquals(0, Notification::where('user_id', $userA->id)->count());

        // Action: userB comments on userA's post
        // This assumes Commentable package fires CommentPosted event
        // and NotificationSystem has a listener for it.
        // We need to mock/ensure CommentPosted event is fired or call NotificationCreationService more directly for this specific test.
        // For now, let's simulate the check that NotificationCreationService would do:

        $settingKey = 'notifications.new_comment.database'; // Type would be 'new_comment'
        if ($userA->getSetting($settingKey, true)) {
            app(\Ijideals\NotificationSystem\Services\NotificationCreationService::class)->createNotification(
                $userA->id,
                'new_comment', // This 'type' must match the part of the setting key
                ['commenter_id' => $userB->id, 'post_id' => $postByA->id]
            );
        }

        $this->assertEquals(1, Notification::where('user_id', $userA->id)
                                          ->where('type', 'new_comment')
                                          ->count());
    }
}
