<?php

namespace Ijideals\ProductSpecifications\Models; // Corrected namespace

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Optional: if you plan to create factories for this model
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecificationKey extends Model
{
    // use HasFactory; // Uncomment if you create a factory

    protected $table = 'specification_keys';

    protected $fillable = [
        'name',
        'type',
        'unit',
    ];

    /**
     * Get the specification values associated with this key.
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductSpecificationValue::class, 'specification_key_id');
    }
}
