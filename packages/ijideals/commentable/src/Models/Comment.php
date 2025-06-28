<?php

namespace Ijideals\Commentable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        if (config('commentable.soft_deletes', true)) {
            self::traitUsesSoftDeletes();
        }
    }

    /**
     * Dynamically use SoftDeletes trait if enabled in config.
     */
    protected static function traitUsesSoftDeletes()
    {
        if (config('commentable.soft_deletes', true) && !in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::addTrait(SoftDeletes::class);
        }
    }

    /**
     * Initialize the model, ensuring SoftDeletes is used if configured.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static::traitUsesSoftDeletes(); // Ensure trait is applied on instantiation as well
    }


    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('commentable.table_name', 'comments');
    }

    /**
     * The user who posted the comment.
     */
    public function commenter(): BelongsTo
    {
        return $this->belongsTo(config('commentable.user_model', \App\Models\User::class), 'user_id');
    }

    /**
     * The model that this comment belongs to (the parent commentable model like Post, Article, etc.).
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Replies to this comment (child comments).
     */
    public function replies(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * The parent comment if this is a reply.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Check if the comment is approved.
     * For simplicity, we'll assume comments are auto-approved if no 'approved_at' field is used.
     * This can be expanded with a dedicated approval workflow.
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        if (array_key_exists('approved_at', $this->casts)) {
            return !is_null($this->approved_at);
        }
        return true; // Auto-approved if approval mechanism isn't explicitly set up
    }

    /**
     * Scope a query to only include approved comments.
     */
    public function scopeApproved($query)
    {
        if (array_key_exists('approved_at', (new static)->casts)) {
            return $query->whereNotNull('approved_at');
        }
        return $query; // No approval status to filter by
    }

    /**
     * Scope a query to only include top-level comments (not replies).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        // Attempt to use a package-specific factory if it exists
        $factory = \Ijideals\Commentable\Database\Factories\CommentFactory::class;
        if (class_exists($factory)) {
            return $factory::new();
        }
        // Fallback or error if no factory is found.
        // For now, let Eloquent handle it or throw an error if factory() is called.
        return new class extends \Illuminate\Database\Eloquent\Factories\Factory {
            protected $model = Comment::class;
            public function definition() {
                return [
                    'user_id' => config('commentable.user_model', \App\Models\User::class)::factory(),
                    'content' => $this->faker->paragraph,
                    // commentable_id and commentable_type would be set by the caller
                ];
            }
        };
    }
}
