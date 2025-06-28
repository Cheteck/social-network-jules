<?php

namespace Ijideals\SocialPosts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User; // Assumant que le modèle User de l'application est ici
use Ijideals\Likeable\Concerns\CanBeLiked;
use Ijideals\Likeable\Contracts\LikeableContract;
use Ijideals\Commentable\Concerns\CanBeCommentedOn;
use Ijideals\Commentable\Contracts\CommentableContract;
use Ijideals\MediaUploader\Concerns\HasMedia;
use Ijideals\HashtagSystem\Traits\HasHashtags; // Import HasHashtags trait
use Laravel\Scout\Searchable; // Import Scout's Searchable trait

/**
 * Class Post
 * @package Ijideals\SocialPosts\Models
 * @property int $id
 * @property string $content
 * @property int $author_id
 * @property string $author_type
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Model $author
 */
class Post extends Model implements LikeableContract, CommentableContract
{
    use HasFactory, CanBeLiked, CanBeCommentedOn, HasMedia, HasHashtags, Searchable; // Use Scout's Searchable trait and HasHashtags

    protected $fillable = [
        'content',
        'author_id',
        'author_type',
    ];

    /**
     * Get the parent author model (can be User or any other model).
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, self>
     */
    public function author(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    // Example accessor for post images (assuming a collection named 'images')
    public function getPostImagesAttribute(): \Illuminate\Support\Collection
    {
        return $this->getMedia('images');
    }

    public function getFirstPostImageUrlAttribute(): ?string
    {
        $image = $this->getFirstMedia('images');
        return $image ? $image->getFullUrl() : null;
    }


    // La méthode user() est supprimée car l'auteur est maintenant polymorphe.
    // Si un accès direct au modèle User est nécessaire, il faudra le gérer spécifiquement.

    /**
     * Create a new factory instance for the model.
     *
     * @return \IJIDeals\SocialPosts\Database\Factories\PostFactory
     */
    protected static function newFactory()
    {
        return \Ijideals\SocialPosts\Database\Factories\PostFactory::new();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            // We could also index author name here if needed for searching posts by author name directly
            // 'author_name' => $this->author?->name,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'posts_index';
    }
}
