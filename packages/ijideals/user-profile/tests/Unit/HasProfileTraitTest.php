<?php

namespace Ijideals\UserProfile\Tests\Unit;

use Ijideals\UserProfile\Models\UserProfile;
use Ijideals\UserProfile\Tests\TestCase; // Adjusted to use the package's TestCase
use App\Models\User; // Main app User model
use Illuminate\Foundation\Testing\RefreshDatabase;

class HasProfileTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_their_profile()
    {
        $user = $this->createUser();

        // Access the profile via the accessor
        $profile = $user->profile;

        $this->assertInstanceOf(UserProfile::class, $profile);
        $this->assertEquals($user->id, $profile->user_id);
        // Check if it was persisted by firstOrCreate
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id]);
    }

    public function test_profile_is_created_on_first_access_if_not_exists()
    {
        $user = $this->createUser();

        $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id]);

        $profile = $user->profile; // Accessor should trigger creation

        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id]);
        $this->assertEquals($user->id, $profile->user_id);
    }

    public function test_existing_profile_is_returned()
    {
        $user = $this->createUser();
        $createdProfile = UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Existing bio'
        ]);

        $profile = $user->profile;

        $this->assertEquals($createdProfile->id, $profile->id); // Should be the same instance id if Eloquent handles it well
        $this->assertEquals('Existing bio', $profile->bio);
    }

    public function test_user_profile_relation_returns_has_one_profile()
    {
        $user = $this->createUser();
        UserProfile::factory()->for($user)->create(['bio' => 'Test bio for relation.']);

        // Test the actual relationship method
        $relatedProfile = $user->userProfile()->first();
        $this->assertInstanceOf(UserProfile::class, $relatedProfile);
        $this->assertEquals('Test bio for relation.', $relatedProfile->bio);
        $this->assertEquals($user->id, $relatedProfile->user_id);
    }

    public function test_profile_accessor_caches_profile_instance_after_first_load()
    {
        $user = $this->createUser();

        // First access - should load or create
        $profile1 = $user->profile;
        $this->assertInstanceOf(UserProfile::class, $profile1);

        // Modify it slightly to see if we get the same instance back
        $profile1->bio = "Temporary Bio";
        // Note: accessor uses firstOrCreate, so this change might not be persisted unless profile1->save() is called
        // The default accessor logic will fetch or create. If we want to test caching of the *instance*,
        // we need to ensure the relation is loaded.

        // The accessor will re-evaluate `firstOrCreate` if the relation isn't "loaded" in a specific way.
        // However, Eloquent's `getRelationValue` (which accessors use) does cache.
        // Let's verify by object hash or by checking a persisted change.
        $profile1->save(); // Persist the change

        $profile2 = $user->profile; // Second access
        $this->assertSame($profile1, $profile2, "Profile instance should be cached on the User model.");
        $this->assertEquals("Temporary Bio", $profile2->bio);
    }
}
