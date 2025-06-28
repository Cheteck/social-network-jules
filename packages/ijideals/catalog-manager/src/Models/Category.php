<?php

namespace Ijideals\CatalogManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('catalog-manager.tables.categories', 'categories');
    }

    /**
     * Generate a slug when setting the name attribute or if slug is empty.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug']) || $this->isDirty('name')) {
            $this->attributes['slug'] = $this->generateUniqueSlug($value);
        }
    }

    /**
     * Generate a unique slug for the category.
     */
    protected function generateUniqueSlug(string $name, ?int $parentId = null): string
    {
        // Slug based on name and parent_id to allow same name in different branches
        $slug = Str::slug($name);
        if ($parentId) {
            // To make it truly unique per parent, we might need parent's slug prefix.
            // For now, just simple slug, uniqueness check will handle conflicts.
        }

        $originalSlug = $slug;
        $count = 1;

        $query = static::where('slug', $slug)->where('id', '!=', $this->id ?? null);
        // Scope uniqueness to the same parent if categories are hierarchical and parent_id is part of unique constraint
        // if (array_key_exists('parent_id', $this->attributes)) {
        //     $query->where('parent_id', $this->attributes['parent_id']);
        // }

        while ($query->clone()->exists()) { // Use clone on query before re-checking existence
            $slug = $originalSlug . '-' . $count++;
            $query = static::where('slug', $slug)->where('id', '!=', $this->id ?? null);
            // if (array_key_exists('parent_id', $this->attributes)) {
            //     $query->where('parent_id', $this->attributes['parent_id']);
            // }
        }
        return $slug;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Children categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * All descendants (children, grandchildren, etc.).
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants'); // Recursive relationship
    }

    /**
     * Products belonging to this category.
     */
    public function products(): BelongsToMany
    {
        $productModelClass = config('catalog-manager.product_model', \Ijideals\CatalogManager\Models\Product::class);
        $pivotTable = config('catalog-manager.tables.category_product', 'category_product');
        return $this->belongsToMany($productModelClass, $pivotTable);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
       return \Ijideals\CatalogManager\Database\Factories\CategoryFactory::new();
    }
}
