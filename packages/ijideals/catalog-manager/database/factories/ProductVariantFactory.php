<?php

    namespace Ijideals\CatalogManager\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\CatalogManager\Models\Product;
    use Ijideals\CatalogManager\Models\ProductVariant;
    use Ijideals\CatalogManager\Models\ProductOptionValue;

    class ProductVariantFactory extends Factory
    {
        protected $model = ProductVariant::class;

        public function definition(): array
        {
            // A variant must belong to a base product
            $product = Product::factory()->create();

            return [
                'product_id' => $product->id,
                'sku' => $this->faker->optional(0.9)->unique()->ean8 . '-VAR',
                // 'price' => $this->faker->randomFloat(2, $product->price - 5, $product->price + 20), // If variant has absolute price
                'price_modifier' => $this->faker->randomElement([null, $this->faker->randomFloat(2, -5, 20)]), // Can be null
                'stock_quantity' => $this->faker->numberBetween(0, 50),
                'manage_stock' => config('catalog-manager.stock_management_enabled', true),
                'is_active' => true,
                'properties' => null,
            ];
        }

        /**
         * Configure the factory to associate specific option values.
         *
         * @param \Illuminate\Support\Collection|array $optionValueIds
         * @return static
         */
        public function withOptionValues($optionValueIds)
        {
            return $this->afterCreating(function (ProductVariant $variant) use ($optionValueIds) {
                $variant->optionValues()->sync($optionValueIds);
            });
        }

        /**
         * Define a variant for a specific product.
         */
        public function forProduct(Product $product)
        {
            return $this->state(['product_id' => $product->id]);
        }
    }
