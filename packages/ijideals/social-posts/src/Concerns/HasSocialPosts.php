<?php

namespace Ijideals\SocialPosts\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ijideals\SocialPosts\Models\Post; // Corrected use statement

trait HasSocialPosts
{
    /**
     * Get all of the model's posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\Ijideals\SocialPosts\Models\Post>
     */
    public function posts(): MorphMany
    {
        return $this->morphMany(Post::class, 'author');
    }
}
