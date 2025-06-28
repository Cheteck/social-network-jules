<?php

namespace Ijideals\Likeable\Tests\Unit;

use Ijideals\Likeable\Concerns\CanLike;
use Ijideals\Likeable\Contracts\LikeableContract;
use Ijideals\Likeable\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CanLikeTraitTest extends TestCase
{
    use RefreshDatabase;

    /** @var \App\Models\User|\Ijideals\Likeable\Concerns\CanLike */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user model instance that uses the CanLike trait for testing
        $this->user = new class extends \App\Models\User {
            use CanLike;
            // If CanLike expects a specific table or attributes not on base User, mock/stub them
            protected $table = 'users'; // Ensure it uses the users table for getKey()
        };
        $this->user->id = 1; // Simulate an existing user
        // No need to save it if we mock the likeable interaction
    }

    public function test_user_can_like_a_likeable_model()
    {
        /** @var LikeableContract|\Mockery\MockInterface $likeableMock */
        $likeableMock = Mockery::mock(Model::class, LikeableContract::class);
        $likeableMock->shouldReceive('getKey')->andReturn(1); // Example ID for the likeable model
        $likeableMock->shouldReceive('getMorphClass')->andReturn('post'); // Example morph class

        // Mock the likers relationship and its attach method
        $relationMock = Mockery::mock();
        $relationMock->shouldReceive('attach')->with($this->user->getKey())->once();
        $likeableMock->shouldReceive('likers')->andReturn($relationMock);

        // Mock the isLikedBy method on the likeable to return false initially
        $likeableMock->shouldReceive('isLikedBy')->with($this->user)->andReturn(false);

        $this->user->like($likeableMock);

        // Assertions are handled by Mockery expectations (once())
        $this->assertTrue(true); // Keep PHPUnit happy
    }

    public function test_user_does_not_re_like_an_already_liked_model()
    {
        /** @var LikeableContract|\Mockery\MockInterface $likeableMock */
        $likeableMock = Mockery::mock(Model::class, LikeableContract::class);

        // Mock the isLikedBy method on the likeable to return true
        $likeableMock->shouldReceive('isLikedBy')->with($this->user)->andReturn(true);

        // likers()->attach() should NOT be called
        $relationMock = Mockery::mock();
        $relationMock->shouldNotReceive('attach');
        $likeableMock->shouldReceive('likers')->andReturn($relationMock);


        $this->user->like($likeableMock);

        $this->assertTrue(true); // Keep PHPUnit happy
    }

    public function test_user_can_unlike_a_likeable_model()
    {
        /** @var LikeableContract|\Mockery\MockInterface $likeableMock */
        $likeableMock = Mockery::mock(Model::class, LikeableContract::class);
        $likeableMock->shouldReceive('getKey')->andReturn(1);
        $likeableMock->shouldReceive('getMorphClass')->andReturn('post');

        $relationMock = Mockery::mock();
        $relationMock->shouldReceive('detach')->with($this->user->getKey())->once();
        $likeableMock->shouldReceive('likers')->andReturn($relationMock);

        $this->user->unlike($likeableMock);

        $this->assertTrue(true);
    }

    public function test_user_can_toggle_like_on_a_likeable_model()
    {
        /** @var LikeableContract|\Mockery\MockInterface $likeableMock */
        $likeableMock = Mockery::mock(Model::class, LikeableContract::class);
        $likeableMock->shouldReceive('getKey')->andReturn(1);
        $likeableMock->shouldReceive('getMorphClass')->andReturn('post');

        $relationMock = Mockery::mock();
        $relationMock->shouldReceive('toggle')->with($this->user->getKey())->once();
        $likeableMock->shouldReceive('likers')->andReturn($relationMock);

        $this->user->toggleLike($likeableMock);

        $this->assertTrue(true);
    }

    public function test_has_liked_checks_if_user_liked_model()
    {
        /** @var LikeableContract|\Mockery\MockInterface $likeableMock */
        $likeableMock = Mockery::mock(Model::class, LikeableContract::class);

        $likeableMock->shouldReceive('isLikedBy')->with($this->user)->andReturn(true);
        $this->assertTrue($this->user->hasLiked($likeableMock));

        $likeableMock->shouldReceive('isLikedBy')->with($this->user)->andReturn(false);
        $this->assertFalse($this->user->hasLiked($likeableMock));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
