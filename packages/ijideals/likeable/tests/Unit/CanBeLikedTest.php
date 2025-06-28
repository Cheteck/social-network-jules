<?php

namespace Ijideals\Likeable\Tests\Unit;

use Ijideals\Likeable\Concerns\CanBeLiked;
use Ijideals\Likeable\Contracts\LikeableContract;
use Ijideals\Likeable\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; // Main app User model

// Test Model implementing CanBeLiked
class LikeableTestModel extends Model implements LikeableContract
{
    use CanBeLiked;
    protected $table = 'likeable_test_models'; // Use a test table
    protected $guarded = [];
    public $timestamps = false; // For simplicity in this test model

    // Dummy relation for testing bootCanBeLiked with soft deletes (if we were testing that)
    // public function likers() { return $this->morphToMany(User::class, 'likeable', 'likes'); }
}


class CanBeLikedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary table for LikeableTestModel
        Schema::create('likeable_test_models', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            // Add other columns if your trait expects them or for testing
        });

        // The 'likes' table is created by the package's main migration,
        // loaded in TestCase::setUp()
    }

    public function test_model_can_be_liked_by_a_user()
    {
        $user = $this->createUser();
        $likeable = LikeableTestModel::create(['name' => 'Test Item']);

        $likeable->likers()->attach($user->id);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_id' => $likeable->id,
            'likeable_type' => $likeable->getMorphClass() // LikeableTestModel::class or its morph map alias
        ]);
        $this->assertTrue($likeable->isLikedBy($user));
    }

    public function test_is_liked_by_returns_false_if_not_liked()
    {
        $user = $this->createUser();
        $anotherUser = $this->createUser();
        $likeable = LikeableTestModel::create();

        $likeable->likers()->attach($user->id); // Liked by $user

        $this->assertFalse($likeable->isLikedBy($anotherUser)); // Not liked by $anotherUser
        $this->assertFalse($likeable->isLikedBy()); // Not liked by guest (Auth::user() is null)
    }

    public function test_likes_count_returns_correct_number()
    {
        $likeable = LikeableTestModel::create();
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $this->assertEquals(0, $likeable->likesCount());
        $this->assertEquals(0, $likeable->likes_count); // Test accessor

        $likeable->likers()->attach($user1->id);
        $this->assertEquals(1, $likeable->likesCount());
        $this->assertEquals(1, $likeable->refresh()->likes_count); // Refresh to get withCount if it was used

        $likeable->likers()->attach($user2->id);
        $this->assertEquals(2, $likeable->likesCount());
        $this->assertEquals(2, $likeable->refresh()->likes_count);
    }

    public function test_deleting_likeable_model_deletes_its_likes()
    {
        // This test relies on the bootCanBeLiked method in the trait
        $user = $this->createUser();
        $likeable = LikeableTestModel::create();

        $likeable->likers()->attach($user->id);
        $this->assertDatabaseHas('likes', [
            'likeable_id' => $likeable->id,
            'likeable_type' => $likeable->getMorphClass()
        ]);
        $this->assertEquals(1, $likeable->likesCount());

        $likeable->delete(); // This should trigger bootCanBeLiked -> deleting hook

        $this->assertDatabaseMissing('likes', [
            'likeable_id' => $likeable->id,
            'likeable_type' => $likeable->getMorphClass()
        ]);
    }

    // Test with withCount to ensure accessor uses it if available
    public function test_likes_count_accessor_uses_with_count_if_available()
    {
        $likeable = LikeableTestModel::create();
        $user1 = $this->createUser();
        $likeable->likers()->attach($user1->id);

        // Fetch model with count
        $fetchedLikeable = LikeableTestModel::withCount('likers')->find($likeable->id);

        $this->assertEquals(1, $fetchedLikeable->likes_count); // Should use the loaded 'likers_count'
        // Verify it didn't run a separate query for count
        // This is harder to test directly without query logging/mocking DB facade
    }
}
