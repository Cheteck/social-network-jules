<?php

namespace Ijideals\CatalogManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany; // Added for variants
use Illuminate\Support\Str;
use Ijideals\MediaUploader\Concerns\HasMedia;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, HasMedia, Searchable;

    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'decimal:2', // Example: store price as decimal with 2 places
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'properties' => 'array', // For custom product attributes/specifications
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable()
    {
        return config('catalog-manager.tables.products', 'products');
    }

    /**
     * Generate a slug when setting the name attribute or if slug is empty.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug']) || $this->isDirty('name')) {
            // Slug should be unique within a shop
            $this->attributes['slug'] = $this->generateUniqueSlug($value, $this->shop_id);
        }
    }

    /**
     * Generate a unique slug for the product within its shop.
     */
    protected function generateUniqueSlug(string $name, int $shopId): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        $query = static::where('slug', $slug)
                       ->where('shop_id', $shopId)
                       ->where('id', '!=', $this->id ?? null);

        while ($query->clone()->exists()) {
            $slug = $originalSlug . '-' . $count++;
             $query = static::where('slug', $slug)
                           ->where('shop_id', $shopId)
                           ->where('id', '!=', $this->id ?? null);
        }
        return $slug;
    }

    /**
     * Get the route key for the model.
     * Typically, products are accessed in context of a shop, so slug might be combined.
     * For direct product access by slug, ensure it's globally unique or use ID.
     * For now, slug is unique per shop.
     */
    // public function getRouteKeyName(): string
    // {
    //     return 'slug';
    // }


    /**
     * The Shop this product belongs to.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(config('catalog-manager.shop_model'), 'shop_id');
    }

    /**
     * Categories this product belongs to.
     */
    public function categories(): BelongsToMany
    {
        $categoryModelClass = config('catalog-manager.category_model', \Ijideals\CatalogManager\Models\Category::class);
        $pivotTable = config('catalog-manager.tables.category_product', 'category_product');
        return $this->belongsToMany($categoryModelClass, $pivotTable);
    }

    /**
     * Get the primary image URL for the product.
     * Assumes 'product_images' collection and takes the first one or a default.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $collectionName = config('catalog-manager.media_collections.product_images.name', 'product_images');
        $media = $this->getFirstMedia($collectionName);
        return $media ? $media->getFullUrl() : null; // Or path to a default placeholder image
    }

    /**
     * Get all image URLs for the product.
     */
    public function getAllImageUrlsAttribute(): array
    {
        $collectionName = config('catalog-manager.media_collections.product_images.name', 'product_images');
        return $this->getMedia($collectionName)->map(fn($media) => $media->getFullUrl())->toArray();
    }

    /**
     * Get the indexable data array for the model (for Laravel Scout).
     */
    public function toSearchableArray(): array
    {
        $array = $this->toArray();
        // Customize array for search indexing
        $searchable = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'sku' => $this->sku, // SKU of the base product
            'shop_id' => $this->shop_id,
            'shop_name' => $this->shop?->name,
            'categories' => $this->categories->pluck('name')->implode(', '), // Comma-separated list of category names
        ];

        // Add variant information
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            $searchable['variant_skus'] = $this->variants->pluck('sku')->filter()->implode(' ');
            $searchable['variant_options'] = $this->variants->map(function ($variant) {
                return $variant->optionValues->pluck('value')->implode(' ');
            })->implode(' | '); // e.g., "Red S | Red M | Blue S"
        } else {
            // If variants are not loaded, or there are none, ensure keys exist for consistency if needed by frontend/search
            $searchable['variant_skus'] = '';
            $searchable['variant_options'] = '';
        }

        return $searchable;
    }

    /**
     * Get the name of the index associated with the model (for Laravel Scout).
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').config('catalog-manager.tables.products', 'products').'_index';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
       return \Ijideals\CatalogManager\Database\Factories\ProductFactory::new();
    }

    /**
     * Product options that apply to this product.
     * Example: A T-Shirt product might have "Color" and "Size" options.
     */
    public function productOptions(): BelongsToMany
    {
        $optionModelClass = config('catalog-manager.product_option_model', ProductOption::class);
        $pivotTable = config('catalog-manager.tables.product_product_option_pivot', 'product_product_option');
        // This pivot table could also store specific values for this product if options are not global values
        // e.g. ->withPivot('allowed_values_json');
        return $this->belongsToMany($optionModelClass, $pivotTable, 'product_id', 'product_option_id');
    }

    /**
     * Variants of this product.
     * Example: "Red T-Shirt, Size M" is a variant of "T-Shirt".
     */
    public function variants(): HasMany
    {
        return $this->hasMany(config('catalog-manager.product_variant_model', ProductVariant::class), 'product_id');
    }

    /**
     * Attach a product option to this product.
     *
     * @param int|ProductOption $option
     * @return void
     */
    public function attachProductOption($option): void
    {
        $this->productOptions()->attach($option);
    }

    /**
     * Detach a product option from this product.
     *
     * @param int|ProductOption $option
     * @return int Number of detached records.
     */
    public function detachProductOption($option): int
    {
        return $this->productOptions()->detach($option);
    }

    /**
     * Sync product options for this product.
     *
     * @param array $optionIds Array of ProductOption IDs.
     * @return array Detach, attach and update results.
     */
    public function syncProductOptions(array $optionIds): array
    {
        return $this->productOptions()->sync($optionIds);
    }

    /**
     * Get the applicable option values for a given product option for this product.
     * For MVP, this returns all global values of the option.
     * Could be extended to allow product-specific subsets of global values if pivot stores allowed_value_ids.
     *
     * @param ProductOption $option
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllowedValuesForOption(ProductOption $option): \Illuminate\Database\Eloquent\Collection
    {
        // Check if this product is actually associated with this option type
        if (!$this->productOptions()->where('product_option_id', $option->id)->exists()) {
            return new \Illuminate\Database\Eloquent\Collection(); // Return empty collection
        }
        // For MVP, return all global values for this option type.
        return $option->values()->orderBy('order_column')->get();
    }
}
