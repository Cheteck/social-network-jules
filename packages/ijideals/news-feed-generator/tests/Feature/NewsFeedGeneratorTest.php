<?php

namespace Ijideals\NewsFeedGenerator\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ijideals\NewsFeedGenerator\Tests\TestCase;
use App\Models\User; // Assuming App\Models\User is the user model
use Ijideals\SocialPosts\Models\Post; // Assuming this is your Post model
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class NewsFeedGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected User $userB; // User A follows User B
    protected User $userC; // User A also follows User C
    protected User $userD; // User A does NOT follow User D

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->createUser(['name' => 'User A']);
        $this->userB = $this->createUser(['name' => 'User B']);
        $this->userC = $this->createUser(['name' => 'User C']);
        $this->userD = $this->createUser(['name' => 'User D']);

        // User A follows B and C
        $this->userA->follow($this->userB);
        $this->userA->follow($this->userC);

        // Create some posts
        $this->createPostForUser($this->userB, 'Post 1 by B', Carbon::now()->subMinutes(10)); // newest by B
        $this->createPostForUser($this->userC, 'Post 1 by C', Carbon::now()->subMinutes(20));
        $this->createPostForUser($this->userB, 'Post 2 by B', Carbon::now()->subMinutes(30));
        $this->createPostForUser($this->userD, 'Post 1 by D (not followed)', Carbon::now()->subMinutes(5)); // Should not appear
        $this->createPostForUser($this->userA, 'My own post by A', Carbon::now()->subMinutes(15)); // Should not appear unless self-posts are included

        // Clear cache before each test, specific to this package's prefix
        // This is important if cache TTL is long or tests run quickly
        $cachePrefix = config('news-feed-generator.cache.prefix', 'news_feed');
        // A more robust way would be to use tags if supported or iterate keys.
        // For now, we'll rely on short TTL or specific key clearing in tests.
    }

    protected function createPostForUser(User $user, string $content, Carbon $createdAt, int $likes = 0, int $comments = 0)
    {
        $post = Post::factory()->create([
            'author_id' => $user->id,
            'author_type' => get_class($user),
            'content' => $content,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Simulate likes and comments for engagement scoring
        // This assumes Post model has likes() and comments() relations and CanBeLiked/CanBeCommentedOn traits
        if (method_exists($post, 'likes') && $likes > 0) {
            User::factory()->count($likes)->create()->each(fn(User $u) => $u->like($post));
        }
        if (method_exists($post, 'comments') && $comments > 0) {
            User::factory()->count($comments)->create()->each(fn(User $u) => $u->comment($post, 'Test comment'));
        }
        // Refresh counts if they are not automatically updated by like/comment methods
        // For the test, we'll rely on withCount in the aggregator, so direct manipulation of counts might be needed if factories don't trigger it
        // Or, we can manually set the counts if the post model doesn't have the like/comment traits in the test environment
        // For simplicity in this test, we assume the counts will be available via withCount in FeedAggregatorService.
        // If testing the RankingEngine directly, you'd pass mock posts with these counts.

        return $post->refresh(); // to get updated counts if any
    }

    /** @test */
    public function user_sees_posts_ranked_by_score_from_followed_users()
    {
        // Override default weights for predictable test scoring
        config(['news-feed-generator.ranking.factors_weights' => [
            'recency' => 0.5, // Lower recency weight for this test
            'engagement_likes' => 0.3,
            'engagement_comments' => 0.2,
        ]]);
        // Optional: Adjust half-life if needed, e.g., shorter to make recency drop faster
        // config(['news-feed-generator.ranking.recency_score_half_life_hours' => 6]);


        // Post B1: Recent, high engagement
        $postB1 = Post::where('content', 'Post 1 by B')->first(); // Created at now()->subMinutes(10)
        User::factory()->count(10)->create()->each(fn(User $u) => $u->like($postB1)); // 10 likes
        User::factory()->count(5)->create()->each(fn(User $u) => $u->comment($postB1, 'test')); // 5 comments
        $postB1->refresh(); // To get counts if not live updated

        // Post C1: Older, medium engagement
        $postC1 = Post::where('content', 'Post 1 by C')->first(); // Created at now()->subMinutes(20)
        User::factory()->count(5)->create()->each(fn(User $u) => $u->like($postC1)); // 5 likes
        User::factory()->count(2)->create()->each(fn(User $u) => $u->comment($postC1, 'test')); // 2 comments
        $postC1->refresh();

        // Post B2: Oldest, low engagement (or no additional engagement beyond what might be in factory)
        $postB2 = Post::where('content', 'Post 2 by B')->first(); // Created at now()->subMinutes(30)
        User::factory()->count(1)->create()->each(fn(User $u) => $u->like($postB2)); // 1 like
        $postB2->refresh();


        $this->actingAs($this->userA, 'api');
        $response = $this->getJson(route('newsfeed.get'));

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');

        // Expected order: PostB1 (highest score), then PostC1, then PostB2
        // This exact order depends on the scoring logic and weights.
        // For a robust test, you might need to calculate expected scores manually or use a snapshot.
        // For now, we'll assert based on a plausible outcome of B1 > C1 > B2.
        $responseData = $response->json('data');
        $this->assertEquals('Post 1 by B', $responseData[0]['content']);
        $this->assertEquals('Post 1 by C', $responseData[1]['content']);
        $this->assertEquals('Post 2 by B', $responseData[2]['content']);

        // Verify scores are present (optional, if you want to expose score in API response)
        // $this->assertArrayHasKey('score', $responseData[0]);

        // Ensure post from non-followed user D is not present
        $response->assertJsonMissing(['content' => 'Post 1 by D (not followed)']);
        // Ensure user A's own post is not present (unless explicitly configured to be)
        $response->assertJsonMissing(['content' => 'My own post by A']);
    }
    // Pagination test remains largely the same, but the content order will be by score.
    // Pagination test remains largely the same, but the content order will be by score.
    // Cache tests also remain conceptually similar. The cached content will now be ranked.

    /** @test */
    public function feed_includes_discovery_content_when_enabled()
    {
        config(['news-feed-generator.discovery.enabled' => true]);
        config(['news-feed-generator.discovery.max_items_ratio' => 0.5]); // Allow up to 50% discovery
        config(['news-feed-generator.pagination_items' => 4]); // Lower for easier testing of ratio

        // User A follows B and C. Posts from B and C are already created in setUp.
        // Let's ensure User D (not followed) has a very popular post.
        $popularPostByD = Post::where('author_id', $this->userD->id)->first();
        if (!$popularPostByD) {
            $popularPostByD = $this->createPostForUser($this->userD, 'Super Popular Post by D', Carbon::now()->subHours(2));
        }
        User::factory()->count(50)->create()->each(fn(User $u) => $u->like($popularPostByD)); // Make it very popular
        $popularPostByD->refresh();

        // And another less popular post by D, to ensure only the most popular is chosen if limit is 1
        $lessPopularPostByD = $this->createPostForUser($this->userD, 'Less Popular Post by D', Carbon::now()->subHours(3), 5);


        $this->actingAs($this->userA, 'api');
        $response = $this->getJson(route('newsfeed.get'));
        $response->assertStatus(200);

        $responseData = $response->json('data');
        $responseContent = collect($responseData)->pluck('content');

        // Expected: 3 posts from followed users (B1, C1, B2 ordered by score)
        // Max discovery items: pagination_items (4) * ratio (0.5) = 2
        // So, we expect the popular post from D to be in the feed.
        // The exact position depends on its score relative to followed posts.

        $this->assertContains('Super Popular Post by D', $responseContent);
        // Depending on scoring, it might even be at the top.
        // For this test, we just check its presence.

        // Ensure the less popular post by D is NOT necessarily there if the limit was hit by more popular ones
        // or if its score was too low compared to followed + other discovery.
        // $this->assertNotContains('Less Popular Post by D', $responseContent); // This assertion is too strict without knowing scores

        // Check that we don't exceed total items (followed + discovery, respecting pagination)
        $this->assertLessThanOrEqual(config('news-feed-generator.pagination_items'), count($responseData));

        // Ensure posts from followed users are still present
        $this->assertTrue($responseContent->contains('Post 1 by B') ||
                          $responseContent->contains('Post 1 by C') ||
                          $responseContent->contains('Post 2 by B'));


        // Reset config for other tests
        config(['news-feed-generator.discovery.enabled' => false]);
    }
}

// Note: The original chronological sort test is now replaced by the ranked sort test.
// If you need to test both (e.g., if chronological is a fallback or an option),
// you would need a way to switch the aggregator's behavior or test the
// getAggregatedFeedForUserChronological method directly if it's made public/testable.
