<?php

namespace IJIDeals\MentionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User; // Assuming your User model is in App\Models

class Mention extends Model
{
    protected $table = 'mentions';

    protected $fillable = [
        'user_id',
        'mentioner_id',
        'mentionable_id',
        'mentionable_type',
    ];

    /**
     * Get the user who was mentioned.
     */
    public function user(): BelongsTo
    {
        // Ensure the User model namespace is correct for your application
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who made the mention.
     */
    public function mentioner(): BelongsTo
    {
        // Ensure the User model namespace is correct for your application
        return $this->belongsTo(User::class, 'mentioner_id');
    }

    /**
     * Get the parent mentionable model (e.g., Post or Comment).
     */
    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }
}
