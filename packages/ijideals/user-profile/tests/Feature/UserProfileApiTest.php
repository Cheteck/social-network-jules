<?php

namespace Ijideals\UserProfile\Tests\Feature;

use Ijideals\UserProfile\Models\UserProfile;
use Ijideals\UserProfile\Tests\TestCase; // Package's TestCase
use App\Models\User; // Main app User model
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum; // If using Sanctum for API auth

class UserProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected UserProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        // The HasProfile trait should create an empty profile on first access or via factory.
        // Let's ensure it's created for clarity in tests that need an existing profile.
        $this->profile = $this->user->profile; // This will trigger firstOrCreate via accessor
        $this->profile->bio = 'Initial bio';
        $this->profile->save();
    }

    public function test_can_get_any_users_profile()
    {
        $otherUser = $this->createUser();
        $otherProfile = $otherUser->profile; // Ensure profile exists
        $otherProfile->bio = "Other user's bio";
        $otherProfile->website = "http://otheruser.com";
        $otherProfile->save();

        $response = $this->getJson(route('users.profile.show', $otherUser));

        $response->assertOk()
            ->assertJsonFragment(['bio' => "Other user's bio"])
            ->assertJsonFragment(['website' => "http://otheruser.com"])
            ->assertJsonPath('user_id', $otherUser->id);
    }

    public function test_can_get_authenticated_users_own_profile()
    {
        Sanctum::actingAs($this->user);

        $this->profile->location = "Test Location";
        $this->profile->save();

        $response = $this->getJson(route('profile.show'));

        $response->assertOk()
            ->assertJsonFragment(['bio' => 'Initial bio'])
            ->assertJsonFragment(['location' => "Test Location"])
            ->assertJsonPath('user_id', $this->user->id);
    }

    public function test_guest_cannot_get_current_profile_endpoint()
    {
        $response = $this->getJson(route('profile.show'));
        $response->assertUnauthorized(); // Expect 401 if not authenticated
    }

    public function test_authenticated_user_can_update_their_own_profile()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'bio' => 'Updated bio.',
            'website' => 'https://updated-website.com',
            'location' => 'New Location',
            'birth_date' => '1990-05-15',
        ];

        $response = $this->putJson(route('profile.update'), $updateData);

        $response->assertOk()
            ->assertJsonFragment(['bio' => 'Updated bio.'])
            ->assertJsonFragment(['website' => 'https://updated-website.com'])
            ->assertJsonFragment(['location' => 'New Location'])
            ->assertJsonFragment(['birth_date' => '1990-05-15T00:00:00.000000Z']); // Date gets cast with time

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'bio' => 'Updated bio.',
            'website' => 'https://updated-website.com',
        ]);
    }

    public function test_profile_update_validates_data()
    {
        Sanctum::actingAs($this->user);

        $invalidData = [
            'website' => 'not-a-url',
            'birth_date' => 'not-a-date',
        ];
        $response = $this->putJson(route('profile.update'), $invalidData);
        $response->assertStatus(422) // Unprocessable Entity for validation errors
            ->assertJsonValidationErrors(['website', 'birth_date']);

        // Test future date for birth_date
        $futureDate = now()->addYear()->format('Y-m-d');
        $response = $this->putJson(route('profile.update'), ['birth_date' => $futureDate]);
        $response->assertStatus(422)->assertJsonValidationErrors(['birth_date']);
    }

    public function test_profile_update_handles_nullable_fields()
    {
        Sanctum::actingAs($this->user);

        // Initial save with some data
        $this->profile->website = "http://initial.com";
        $this->profile->location = "Initial Location";
        $this->profile->save();

        $updateData = [
            'bio' => 'Bio updated, other fields nulled.',
            'website' => null, // Explicitly setting to null
            'location' => null,
            // birth_date not provided, should remain as is or null if not set
        ];

        $response = $this->putJson(route('profile.update'), $updateData);
        $response->assertOk()
                 ->assertJsonFragment(['bio' => 'Bio updated, other fields nulled.'])
                 ->assertJsonFragment(['website' => null])
                 ->assertJsonFragment(['location' => null]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'website' => null,
            'location' => null,
        ]);
    }

    public function test_guest_cannot_update_profile()
    {
        $updateData = ['bio' => 'Guest bio update attempt.'];
        $response = $this->putJson(route('profile.update'), $updateData);
        $response->assertUnauthorized();
    }
}
