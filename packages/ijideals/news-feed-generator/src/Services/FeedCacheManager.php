<?php

namespace Ijideals\NewsFeedGenerator\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log; // Optional: for logging cache misses/errors

class FeedCacheManager
{
    protected CacheRepository $cache;
    protected string $cachePrefix;
    protected int $cacheTtlMinutes;

    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
        $this->cachePrefix = config('news-feed-generator.cache.prefix', 'news_feed');
        $this->cacheTtlMinutes = config('news-feed-generator.cache.ttl', 60);
    }

    /**
     * Generate a unique cache key for a user's feed.
     *
     * @param int|string $userId
     * @param int $page
     * @return string
     */
    protected functiongetCacheKey(int|string $userId, int $page = 1): string
    {
        return "{$this->cachePrefix}:user:{$userId}:page:{$page}";
    }

    /**
     * Attempt to retrieve a user's feed from the cache.
     *
     * @param int|string $userId
     * @param int $page
     * @return mixed Returns the cached feed data (likely an array or PaginatedResource) or null if not found.
     */
    public functiongetFeed(int|string $userId, int $page = 1): mixed
    {
        $cacheKey = $this->getCacheKey($userId, $page);

        // Log::debug("Attempting to get feed from cache with key: {$cacheKey}"); // Optional
        return $this->cache->get($cacheKey);
    }

    /**
     * Store a user's feed in the cache.
     *
     * @param int|string $userId
     * @param int $page
     * @param mixed $feedData The feed data to cache.
     * @return bool
     */
    public functionstoreFeed(int|string $userId, int $page = 1, mixed $feedData): bool
    {
        $cacheKey = $this->getCacheKey($userId, $page);
        $ttlSeconds = $this->cacheTtlMinutes * 60;

        // Log::debug("Storing feed in cache with key: {$cacheKey} for {$ttlSeconds} seconds."); // Optional
        return $this->cache->put($cacheKey, $feedData, $ttlSeconds);
    }

    /**
     * Clear a specific user's feed cache (all pages or a specific page).
     *
     * @param int|string $userId
     * @param int|null $page If null, clears all pages for the user.
     * @return void
     */
    public functionclearUserFeedCache(int|string $userId, ?int $page = null): void
    {
        if ($page !== null) {
            $cacheKey = $this->getCacheKey($userId, $page);
            // Log::debug("Clearing user feed cache for key: {$cacheKey}"); // Optional
            $this->cache->forget($cacheKey);
        } else {
            // This is more complex as it requires knowing all page keys or using tags.
            // For simplicity in MVP, we might only clear specific pages or rely on TTL.
            // If using Redis or a cache that supports tags, you could tag user feeds.
            // Example with tags (if supported and $this->cache is taggable):
            // $this->cache->tags(["feed_user_{$userId}"])->flush();

            // For now, this method might be more of a placeholder for more advanced cache clearing.
            // A common approach is to clear page 1, as users often land there.
            // Or, don't clear aggressively and let TTL handle it for MVP.
            Log::warning("Attempted to clear all feed pages for user {$userId}, but this feature is not fully implemented for non-tagged caches. Clear specific pages or rely on TTL.");
        }
    }

    /**
     * Clear all news feed caches.
     * Be cautious with this in production.
     *
     * @return void
     */
    public functionclearAllFeedCaches(): void
    {
        // This is highly dependent on the cache driver and prefixing strategy.
        // If using Redis and a consistent prefix, you might use SCAN and DEL.
        // For file/database cache, it's harder without tags.
        // $this->cache->flush(); // This would flush the ENTIRE cache store, which is usually too broad.
        Log::warning("Attempted to clear ALL news feed caches. This is a broad operation and might not be fully supported for all cache drivers without tags. Ensure your prefixing and cache driver allow targeted flushing if needed.");
        // For MVP, this might remain a no-op or a warning.
    }
}
