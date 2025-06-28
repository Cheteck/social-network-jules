<?php

namespace Ijideals\CatalogManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Ijideals\MediaUploader\Concerns\HasMedia; // For variant-specific images

class ProductVariant extends Model
{
    use HasFactory, HasMedia;

    protected $guarded = ['id'];

    protected $table = 'product_variants'; // Explicit table name

    protected $casts = [
        'price_modifier' => 'decimal:2', // Or 'price' => 'decimal:2' if storing absolute price
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'properties' => 'array', // For any variant-specific properties not covered by options
    ];

    /**
     * The base product this variant belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(config('catalog-manager.product_model', Product::class), 'product_id');
    }

    /**
     * The specific option values that define this variant.
     * Example: Variant is "T-Shirt, Color: Red, Size: M".
     * This connects to "Red" (ProductOptionValue) and "M" (ProductOptionValue).
     */
    public function optionValues(): BelongsToMany
    {
        $optionValueModelClass = config('catalog-manager.product_option_value_model', ProductOptionValue::class);
        $pivotTable = config('catalog-manager.tables.product_variant_option_value_pivot', 'product_variant_option_values');
        // If you need to store additional pivot data (e.g., specific surcharge for this value in this variant)
        // ->withPivot('extra_data_column');
        return $this->belongsToMany($optionValueModelClass, $pivotTable, 'product_variant_id', 'product_option_value_id');
    }

    /**
     * Get the final price of the variant.
     * Considers the base product's price and the variant's price_modifier.
     * Or returns its own price if stored directly.
     *
     * @return float
     */
    public function getCalculatedPriceAttribute(): float
    {
        if (isset($this->attributes['price']) && !is_null($this->attributes['price'])) {
            // If variant has its own absolute price field
            return (float) $this->attributes['price'];
        }

        $basePrice = $this->product?->price ?? 0.00;
        $modifier = $this->price_modifier ?? 0.00;

        // Modifier could be additive or a new price. For now, assume additive.
        // A common approach is for price_modifier to be the *difference* from base.
        // Or, variant stores its own full price. Let's assume variant stores its own full price if 'price' attribute exists.
        // If only price_modifier exists, it adjusts the base product price.
        // For simplicity, if variant has 'price', use it. Else, product_price + price_modifier.
        // The current model has 'price_modifier'.

        return (float) ($basePrice + $modifier);
    }

    /**
     * Get a display name for the variant, often combining option values.
     * Example: "Red / Small"
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('optionValues') && $this->optionValues->isNotEmpty()) {
            return $this->optionValues->map(function ($value) {
                //return $value->option->name . ': ' . $value->value; // e.g. Color: Red
                return $value->value; // e.g. Red
            })->implode(' / ');
        }
        // Fallback if optionValues are not loaded or empty
        return $this->sku ?: "Variant #{$this->id}";
    }


    // If variants can be searched directly via Scout
    // public function toSearchableArray(): array
    // {
    //     $array = $this->toArray();
    //     $array['product_name'] = $this->product?->name;
    //     $array['display_name'] = $this->display_name; // Accessor for combined options
    //     // Add other relevant fields for searching variants
    //     return $array;
    // }
    // public function searchableAs(): string
    // {
    //     return config('scout.prefix').'product_variants_index';
    // }


    protected static function newFactory()
    {
       return \Ijideals\CatalogManager\Database\Factories\ProductVariantFactory::new();
    }
}
