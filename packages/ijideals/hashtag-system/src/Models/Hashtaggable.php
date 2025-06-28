<?php

namespace Ijideals\HashtagSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Hashtaggable extends Model
{
    protected $table = 'hashtaggables';

    protected $fillable = [
        'hashtag_id',
        'hashtaggable_id',
        'hashtaggable_type',
    ];

    public $timestamps = false; // Pivots usually don't need timestamps

    /**
     * Get the parent hashtaggable model (Post, etc.).
     */
    public function hashtaggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the hashtag that owns the hashtaggable.
     */
    public function hashtag()
    {
        return $this->belongsTo(Hashtag::class);
    }
}
