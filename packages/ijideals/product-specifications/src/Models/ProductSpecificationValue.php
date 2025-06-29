<?php

namespace Ijideals\ProductSpecifications\Models; // Corrected namespace

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Optional
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ijideals\CatalogManager\Models\Product; // Corrected casing

class ProductSpecificationValue extends Model
{
    // use HasFactory; // Uncomment if you create a factory

    protected $table = 'product_specification_values';

    protected $fillable = [
        'product_id',
        'specification_key_id',
        'value',
    ];

    /**
     * Get the product that this specification value belongs to.
     */
    public function product(): BelongsTo
    {
        // Ensure the Product model namespace is correct for ijideals/catalog-manager
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the specification key that this value pertains to.
     */
    public function specificationKey(): BelongsTo
    {
        return $this->belongsTo(SpecificationKey::class, 'specification_key_id');
    }
}
