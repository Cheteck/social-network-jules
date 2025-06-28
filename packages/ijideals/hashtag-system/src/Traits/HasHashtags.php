<?php

namespace Ijideals\HashtagSystem\Traits;

use Ijideals\HashtagSystem\Models\Hashtag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

trait HasHashtags
{
    /**
     * Get all of the hashtags for the model.
     */
    public function hashtags(): MorphToMany
    {
        return $this->morphToMany(Hashtag::class, 'hashtaggable', 'hashtaggables', 'hashtaggable_id', 'hashtag_id');
    }

    /**
     * Attach one or multiple hashtags to the model.
     *
     * @param string|array|Collection $hashtags A string of space-separated hashtags (e.g. "#tag1 #tag2"),
     *                                          an array of hashtag names (e.g. ["tag1", "tag2"]),
     *                                          or a collection of Hashtag models.
     * @return void
     */
    public function addHashtags($hashtags): void
    {
        $hashtagIds = $this->getHashtagIds($this->parseHashtags($hashtags));
        $this->hashtags()->syncWithoutDetaching($hashtagIds);
    }

    /**
     * Sync the hashtags for the model.
     *
     * @param string|array|Collection $hashtags
     * @return void
     */
    public function syncHashtags($hashtags): void
    {
        $hashtagIds = $this->getHashtagIds($this->parseHashtags($hashtags));
        $this->hashtags()->sync($hashtagIds);
    }

    /**
     * Detach one or multiple hashtags from the model.
     *
     * @param string|array|Collection $hashtags
     * @return void
     */
    public function removeHashtags($hashtags): void
    {
        $hashtagIds = $this->getHashtagIds($this->parseHashtags($hashtags), false);
        if (!empty($hashtagIds)) {
            $this->hashtags()->detach($hashtagIds);
        }
    }

    /**
     * Detach all hashtags from the model.
     *
     * @return void
     */
    public function removeAllHashtags(): void
    {
        $this->hashtags()->detach();
    }

    /**
     * Parse the given hashtags input.
     *
     * @param string|array|Collection $hashtags
     * @return array
     */
    protected function parseHashtags($hashtags): array
    {
        if (is_string($hashtags)) {
            // Match hashtags starting with #, extract the word part
            preg_match_all('/#([\p{L}\p{N}_-]+)/u', $hashtags, $matches);
            return array_unique(array_filter(array_map('strtolower', $matches[1])));
        }

        if ($hashtags instanceof Collection) {
            return $hashtags->map(function ($tag) {
                if ($tag instanceof Hashtag) {
                    return strtolower(Str::startsWith($tag->name, '#') ? substr($tag->name, 1) : $tag->name);
                }
                return strtolower(Str::startsWith($tag, '#') ? substr($tag, 1) : $tag);
            })->all();
        }

        return array_unique(array_filter(array_map(function($tag) {
             return strtolower(Str::startsWith($tag, '#') ? substr($tag, 1) : $tag);
        }, Arr::wrap($hashtags))));
    }

    /**
     * Get the IDs of the given hashtags, creating them if they don't exist.
     *
     * @param array $hashtagNames
     * @param bool $create Create hashtags if they do not exist
     * @return array
     */
    protected function getHashtagIds(array $hashtagNames, bool $create = true): array
    {
        $hashtagNames = array_filter($hashtagNames);
        if (empty($hashtagNames)) {
            return [];
        }

        $existingHashtags = Hashtag::whereIn('name', $hashtagNames)->get()->keyBy('name');
        $hashtagIds = $existingHashtags->pluck('id')->toArray();

        if ($create) {
            $missingNames = array_diff($hashtagNames, $existingHashtags->pluck('name')->toArray());
            foreach ($missingNames as $name) {
                if(empty(trim($name))) continue;
                try {
                    $hashtag = Hashtag::create(['name' => $name, 'slug' => Str::slug($name)]);
                    $hashtagIds[] = $hashtag->id;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle potential race condition if another process created the hashtag
                    // or unique constraint violation for other reasons.
                    $existing = Hashtag::where('name', $name)->orWhere('slug', Str::slug($name))->first();
                    if ($existing) {
                        $hashtagIds[] = $existing->id;
                    } else {
                        // If it still fails, rethrow or log, as it's an unexpected issue.
                        // For now, we'll skip adding this specific tag if it fails after a retry.
                    }
                }
            }
        }
        return array_unique($hashtagIds);
    }
}
