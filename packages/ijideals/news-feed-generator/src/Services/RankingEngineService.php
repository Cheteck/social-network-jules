<?php

namespace Ijideals\NewsFeedGenerator\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Ijideals\SocialPosts\Models\Post; // Assuming this is the Post model

class RankingEngineService
{
    protected array $weights;
    protected int $recencyScoreHalfLifeHours;

    public function __construct()
    {
        $this->weights = config('news-feed-generator.ranking.factors_weights', [
            'recency' => 1.0,
            'engagement_likes' => 0.2, // Example weight
            'engagement_comments' => 0.3, // Example weight
        ]);
        $this->recencyScoreHalfLifeHours = config('news-feed-generator.ranking.recency_score_half_life_hours', 24);
    }

    /**
     * Rank a collection of posts.
     *
     * @param Collection $posts Collection of Post models.
     * @return Collection Sorted collection of Post models with an added 'score' attribute.
     */
    public function rankPosts(Collection $posts): Collection
    {
        if ($posts->isEmpty()) {
            return $posts;
        }

        $scoredPosts = $posts->map(function ($post) {
            // Ensure $post is an instance of the configured post model
            // This check might be more robust depending on how $posts are fetched
            if (!$post instanceof (config('news-feed-generator.post_model'))) {
                 // Log or handle this case - for now, skip scoring if not a valid post model
                // $post->score = 0; // Or some default low score
                return $post;
            }

            $score = 0;
            $score += $this->calculateRecencyScore($post->created_at);
            $score += $this->calculateEngagementScore($post);
            // Add other scoring components here in the future

            $post->score = $score;
            return $post;
        });

        // Sort by score in descending order
        return $scoredPosts->sortByDesc('score');
    }

    /**
     * Calculate the recency score for a post.
     * Uses an exponential decay function based on post age.
     *
     * @param Carbon|string $createdAt
     * @return float
     */
    protected function calculateRecencyScore($createdAt): float
    {
        if (!$createdAt instanceof Carbon) {
            $createdAt = Carbon::parse($createdAt);
        }

        $ageInHours = $createdAt->diffInHours(Carbon::now());
        $weight = $this->weights['recency'] ?? 1.0;

        if ($this->recencyScoreHalfLifeHours <= 0) return $weight; // Avoid division by zero if misconfigured

        // Exponential decay: score = weight * (0.5 ^ (age_in_hours / half_life_hours))
        $score = $weight * (0.5 ** ($ageInHours / $this->recencyScoreHalfLifeHours));

        return (float) $score;
    }

    /**
     * Calculate the engagement score for a post.
     * Based on likes and comments counts.
     *
     * @param mixed $post Assumed to be an instance of the configured Post model
     * @return float
     */
    protected function calculateEngagementScore($post): float
    {
        $score = 0;

        // Likes score
        if (isset($post->likes_count) && isset($this->weights['engagement_likes'])) {
            // Normalize or directly use count. For simplicity, direct use with weight.
            // Logarithmic scaling can be good for engagement counts: log(1 + count)
            $score += (float) (log1p($post->likes_count) * $this->weights['engagement_likes']);
        }

        // Comments score
        if (isset($post->comments_count) && isset($this->weights['engagement_comments'])) {
            $score += (float) (log1p($post->comments_count) * $this->weights['engagement_comments']);
        }

        return (float) $score;
    }

    // Future methods:
    // - calculateAffinityScore(User $user, Post $post)
    // - calculateDiversityScore(Post $post, Collection $currentFeed)
}
