<?php

namespace Ijideals\NewsFeedGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Ijideals\NewsFeedGenerator\Services\FeedAggregatorService;
use Ijideals\NewsFeedGenerator\Services\FeedCacheManager;
// Potentially a FeedResource for transforming the post data
// use Ijideals\NewsFeedGenerator\Http\Resources\FeedItemResource;

class NewsFeedController extends Controller
{
    protected FeedAggregatorService $aggregatorService;
    protected FeedCacheManager $cacheManager;

    public function __construct(
        FeedAggregatorService $aggregatorService,
        FeedCacheManager $cacheManager
    ) {
        $this->middleware('auth:api');
        $this->aggregatorService = $aggregatorService;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get the news feed for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFeed(Request $request)
    {
        $user = Auth::user();
        $page = $request->input('page', 1);

        // Try to get the feed from cache first
        $cachedFeed = $this->cacheManager->getFeed($user->id, $page);

        if ($cachedFeed) {
            // Optional: You might want to log cache hits or return a specific header
            // Log::info("Feed for user {$user->id}, page {$page} served from cache.");
            return response()->json($cachedFeed);
        }

        // If not in cache, generate it
        // Now calls the method that uses the RankingEngineService
        $feedItems = $this->aggregatorService->getRankedFeedForUser($user->id, $page);

        // Store the newly generated feed in cache
        // It's important to cache the final paginated result.
        // The $feedItems from getRankedFeedForUser is already a LengthAwarePaginator.
        if ($feedItems->isNotEmpty()) { // Check if the paginator is not empty
            $this->cacheManager->storeFeed($user->id, $page, $feedItems->toArray()); // Store as array for broader cache compatibility
        }

        // Optional: Transform the items using an API Resource
        // return FeedItemResource::collection($feedItems);

        return response()->json($feedItems);
    }
}
