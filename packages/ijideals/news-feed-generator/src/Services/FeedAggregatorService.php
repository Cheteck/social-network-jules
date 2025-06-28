<?php

namespace Ijideals\NewsFeedGenerator\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class FeedAggregatorService
{
    protected $userModelClass;
    protected $postModelClass;
    protected $batchSize;
    protected $paginationItems;
    protected RankingEngineService $rankingEngine;

    public function __construct(RankingEngineService $rankingEngine)
    {
        $this->userModelClass = config('news-feed-generator.user_model', \App\Models\User::class);
        $this->postModelClass = config('news-feed-generator.post_model', \Ijideals\SocialPosts\Models\Post::class);
        $this->batchSize = config('news-feed-generator.batch_size', 100);
        $this->paginationItems = config('news-feed-generator.pagination_items', 15);
        $this->rankingEngine = $rankingEngine;
    }

    /**
     * Get the aggregated and ranked feed for a given user.
     * Fetches posts from followed users, then ranks them.
     *
     * @param int|string $userId The ID of the user for whom to generate the feed.
     * @param int $page The current page number for pagination.
     * @return LengthAwarePaginator
     */
    public function getRankedFeedForUser(int|string $userId, int $page = 1): LengthAwarePaginator
    {
        $user = $this->userModelClass::find($userId);

        if (!$user) { // Removed method_exists check as it's about the user instance
            return new LengthAwarePaginator([], 0, $this->paginationItems, $page);
        }

        $followedUserIds = [];
        if (method_exists($user, 'followings')) {
            $followedUserIds = $user->followings()->pluck('id')->all();
        }

        // If user follows no one, and discovery is off, feed will be empty.
        // If discovery is on, it might still populate.

        $mainPosts = new Collection();
        if (!empty($followedUserIds)) {
            $postsQuery = $this->postModelClass::whereIn('author_id', $followedUserIds)
                ->where('author_type', (new $this->userModelClass)->getMorphClass());
            // A more advanced approach might fetch posts within a certain recent timeframe.
            // For now, we'll fetch a multiple of paginationItems or up to batch_size.
            // Let's fetch a reasonable number of recent posts to rank.
            // Example: fetch last 2-3 pages worth of items, or up to batch_size.
            // This limit needs careful consideration to balance performance and feed quality.
            // For simplicity, let's take up to batch_size most recent posts from followed users.
            // ->orderBy('created_at', 'desc')
            // ->limit($this->batchSize);
            // orderBy and limit here might be too restrictive before ranking if batchSize is small.
            // Alternative: fetch all relevant posts from followed users within a reasonable time window (e.g., last 7 days)

        // For now, let's remove the orderBy here if ranking is done later,
        // but be mindful of fetching too much data if not time-windowed.
            $postsQuery->orderBy('created_at', 'desc')->limit($this->batchSize); // Get a batch of recent posts

            if (method_exists($this->postModelClass, 'author')) {
                $postsQuery->with('author');
            }
            if (method_exists($this->postModelClass, 'likes')) {
                $postsQuery->withCount('likes');
            }
            if (method_exists($this->postModelClass, 'comments')) {
                $postsQuery->withCount('comments');
            }
            $mainPosts = $postsQuery->get();
        }

        $allPotentialPosts = $mainPosts;

        // --- Discovery Content (Phase 2 addition) ---
        if (config('news-feed-generator.discovery.enabled', false)) {
            $discoveryPosts = $this->getDiscoveryContent($user, $followedUserIds, $mainPosts->pluck('id')->all());
            // Merge and ensure no duplicates if a discovery post was somehow already in mainPosts (e.g. by another followed user)
            $allPotentialPosts = $allPotentialPosts->merge($discoveryPosts)->unique('id');
        }
        // --- End Discovery Content ---

        if ($allPotentialPosts->isEmpty()){
             return new LengthAwarePaginator([], 0, $this->paginationItems, $page);
        }

        // Rank the fetched posts (including discovery items)
        $rankedPosts = $this->rankingEngine->rankPosts($allPotentialPosts);

        // Manually paginate the ranked collection
        $currentPageItems = $rankedPosts->slice(($page - 1) * $this->paginationItems, $this->paginationItems)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $rankedPosts->count(), // Total count should be of all ranked items, not just current page
            $this->paginationItems,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get discovery content for the user.
     * For this phase, "discovery" means globally popular posts from users not followed.
     *
     * @param \App\Models\User $user The user for whom to get discovery content.
     * @param array $followedUserIds IDs of users already followed by the user.
     * @param array $excludedPostIds IDs of posts already fetched (e.g. from followed users).
     * @return Collection
     */
    protected function getDiscoveryContent($user, array $followedUserIds, array $excludedPostIds): Collection
    {
        $discoveryConfig = config('news-feed-generator.discovery', []);
        $maxDiscoveryItemsRatio = $discoveryConfig['max_items_ratio'] ?? 0.2;
        // Calculate how many discovery items to fetch based on pagination size and ratio
        $limitDiscoveryItems = (int) ceil($this->paginationItems * $maxDiscoveryItemsRatio);
        if ($limitDiscoveryItems <= 0) return new Collection();

        // Exclude posts from users the current user follows, and the user themselves
        $excludedAuthorIds = array_merge($followedUserIds, [$user->getKey()]);

        // Fetch popular posts (e.g., by likes_count) from other users
        // This requires 'likes_count' to be available or calculable for posts.
        // The Post model should have `withCount('likes')` available.
        $discoveryQuery = $this->postModelClass::query();

        if(method_exists($this->postModelClass, 'likes')){
            $discoveryQuery->withCount('likes')->orderBy('likes_count', 'desc');
        } else {
            // Fallback if no likes system: just pick recent posts from others
            $discoveryQuery->orderBy('created_at', 'desc');
        }

        $discoveryPosts = $discoveryQuery
            ->whereNotIn('author_id', $excludedAuthorIds)
            // ->where('author_type', (new $this->userModelClass)->getMorphClass()) // Assuming discovery posts are also from Users
            ->whereNotIn('id', $excludedPostIds) // Exclude posts already fetched for the main feed
            ->limit($limitDiscoveryItems) // Limit the number of discovery items
            ->get();

        // Eager load necessary relations for these discovery posts too
        if (!$discoveryPosts->isEmpty()) {
            $relationsToLoad = [];
            if (method_exists($this->postModelClass, 'author')) $relationsToLoad[] = 'author';
            // likes_count already loaded if sorting by it. If not, and needed:
            if (method_exists($this->postModelClass, 'likes') && !isset($discoveryPosts->first()->likes_count)) {
                 $relationsToLoad[] = 'likes'; // or withCount here again
            }
            if (method_exists($this->postModelClass, 'comments')) $relationsToLoad[] = 'comments'; // or withCount

            if (!empty($relationsToLoad)) {
                $discoveryPosts->load($relationsToLoad);
                // If using withCount for likes/comments and it wasn't part of the initial query:
                if (method_exists($this->postModelClass, 'likes') && !isset($discoveryPosts->first()->likes_count)) {
                    $discoveryPosts->loadCount('likes');
                }
                if (method_exists($this->postModelClass, 'comments') && !isset($discoveryPosts->first()->comments_count)) {
                    $discoveryPosts->loadCount('comments');
                }
            }
        }

        return $discoveryPosts;
    }

    // Renaming the old MVP method for clarity or if needed as a fallback
    public function getAggregatedFeedForUserChronological(int|string $userId, int $page = 1): LengthAwarePaginator
    {
        $user = $this->userModelClass::find($userId);

        if (!$user || !method_exists($user, 'followings')) {
            return new LengthAwarePaginator([], 0, $this->paginationItems, $page);
        }
        $followedUserIds = $user->followings()->pluck('id')->all();
        if (empty($followedUserIds)) {
            return new LengthAwarePaginator([], 0, $this->paginationItems, $page);
        }

        $postsQuery = $this->postModelClass::whereIn('author_id', $followedUserIds)
            ->where('author_type', (new $this->userModelClass)->getMorphClass())
            ->orderBy(config('news-feed-generator.ranking.default_sort_column', 'created_at'),
                      config('news-feed-generator.ranking.default_sort_direction', 'desc'));

        if (method_exists($this->postModelClass, 'author')) $postsQuery->with('author');
        if (method_exists($this->postModelClass, 'likes')) $postsQuery->withCount('likes');
        if (method_exists($this->postModelClass, 'comments')) $postsQuery->withCount('comments');

        return $postsQuery->paginate($this->paginationItems, ['*'], 'page', $page);
    }


    // Future methods for more advanced aggregation:
        // This depends on what you want to show in the feed item.
        if (method_exists($this->postModelClass, 'author')) {
            $postsQuery->with('author');
        }
        if (method_exists($this->postModelClass, 'likes')) { // from ijideals/likeable
            $postsQuery->withCount('likes');
        }
        if (method_exists($this->postModelClass, 'comments')) { // from ijideals/commentable
            $postsQuery->withCount('comments');
        }
        // Example for media, if posts can have media from ijideals/media-uploader
        // Assumes a 'media' relation and you want to load a specific collection, e.g., 'post_images'
        // if (method_exists($this->postModelClass, 'media')) {
        //    $postsQuery->with(['media' => function ($query) {
        //        $query->where('collection_name', 'post_images'); // Or your relevant collection
        //    }]);
        // }


        $allPotentialPosts = $postsQuery->get();

        // Rank the fetched posts
        $rankedPosts = $this->rankingEngine->rankPosts($allPotentialPosts);

        // Manually paginate the ranked collection
        $currentPageItems = $rankedPosts->slice(($page - 1) * $this->paginationItems, $this->paginationItems)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $rankedPosts->count(),
            $this->paginationItems,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    // Renaming the old MVP method for clarity or if needed as a fallback
    public function getAggregatedFeedForUserChronological(int|string $userId, int $page = 1): LengthAwarePaginator
    {
        $user = $this->userModelClass::find($userId);

        if (!$user || !method_exists($user, 'followings')) {
            return new LengthAwarePaginator([], 0, $this->paginationItems, $page);
        }
        $followedUserIds = $user->followings()->pluck('id')->all();
        if (empty($followedUserIds)) {
            return new LengthAwarePaginator([], 0, $this->paginationItems, $page);
        }

        $postsQuery = $this->postModelClass::whereIn('author_id', $followedUserIds)
            ->where('author_type', (new $this->userModelClass)->getMorphClass())
            ->orderBy(config('news-feed-generator.ranking.default_sort_column', 'created_at'),
                      config('news-feed-generator.ranking.default_sort_direction', 'desc'));

        if (method_exists($this->postModelClass, 'author')) $postsQuery->with('author');
        if (method_exists($this->postModelClass, 'likes')) $postsQuery->withCount('likes');
        if (method_exists($this->postModelClass, 'comments')) $postsQuery->withCount('comments');

        return $postsQuery->paginate($this->paginationItems, ['*'], 'page', $page);
    }


    // Future methods for more advanced aggregation:
    // - getDiscoveryContent()
    // - getSponsoredContent()
    // - applyContentFilters()
}
