<?php

    namespace Ijideals\CatalogManager\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Relations\BelongsToMany;

    class ProductOption extends Model
    {
        use HasFactory;

        protected $guarded = ['id'];

        protected $table = 'product_options'; // Explicit table name

        /**
         * Get the table associated with the model.
         * Overriding in case config is not loaded yet or for explicitness.
         */
        // public function getTable()
        // {
        //     return config('catalog-manager.tables.product_options', 'product_options');
        // }

        /**
         * Option values associated with this option type.
         * Example: Option "Color" has values "Red", "Blue", "Green".
         */
        public function values(): HasMany
        {
            return $this->hasMany(config('catalog-manager.product_option_value_model', ProductOptionValue::class));
        }

        /**
         * Products that use this option.
         */
        public function products(): BelongsToMany
        {
            $productModelClass = config('catalog-manager.product_model', Product::class);
            $pivotTable = config('catalog-manager.tables.product_product_option_pivot', 'product_product_option');
            return $this->belongsToMany($productModelClass, $pivotTable);
        }

        protected static function newFactory()
        {
           return \Ijideals\CatalogManager\Database\Factories\ProductOptionFactory::new();
        }
    }
