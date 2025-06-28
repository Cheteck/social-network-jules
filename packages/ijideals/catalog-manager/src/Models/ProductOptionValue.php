<?php

    namespace Ijideals\CatalogManager\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\BelongsToMany;

    class ProductOptionValue extends Model
    {
        use HasFactory;

        protected $guarded = ['id'];

        protected $table = 'product_option_values'; // Explicit table name

        /**
         * The option type this value belongs to.
         * Example: Value "Red" belongs to Option "Color".
         */
        public function option(): BelongsTo
        {
            return $this->belongsTo(config('catalog-manager.product_option_model', ProductOption::class), 'product_option_id');
        }

        /**
         * Product variants that have this specific option value.
         */
        public function variants(): BelongsToMany
        {
            $variantModelClass = config('catalog-manager.product_variant_model', ProductVariant::class);
            $pivotTable = config('catalog-manager.tables.product_variant_option_value_pivot', 'product_variant_option_values');
            return $this->belongsToMany($variantModelClass, $pivotTable);
        }

        protected static function newFactory()
        {
           return \Ijideals\CatalogManager\Database\Factories\ProductOptionValueFactory::new();
        }
    }
