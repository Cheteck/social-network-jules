<?php

namespace Ijideals\Likeable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    protected $guarded = [];

    protected $casts = [
        // No specific casts needed for now, but good to have
    ];

    public function getTable()
    {
        return config('likeable.table_name', 'likes');
    }

    /**
     * The model that was liked.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who performed the like.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        // Use the user model specified in the config
        return $this->belongsTo(config('likeable.user_model', \App\Models\User::class), 'user_id');
    }

    /**
     * Alias for user() method, common in some contexts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function liker(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Ijideals\Likeable\Database\Factories\LikeFactory::new();
    }
}
