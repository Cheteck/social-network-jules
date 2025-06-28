<?php

    namespace Ijideals\CatalogManager\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\CatalogManager\Models\Product;
    use Ijideals\ShopManager\Models\Shop; // Dependency
    use App\Models\User; // For shop owner

    class ProductFactory extends Factory
    {
        protected $model = Product::class;

        public function definition(): array
        {
            // Ensure a shop exists or create one
            $shop = Shop::first() ?? Shop::factory()->create(['owner_id' => User::factory()]);

            return [
                'shop_id' => $shop->id,
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->paragraph,
                'price' => $this->faker->randomFloat(2, 5, 1000),
                'sku' => $this->faker->optional(0.8)->unique()->ean8, // SKU is optional but unique if present
                'stock_quantity' => $this->faker->numberBetween(0, 200),
                'manage_stock' => config('catalog-manager.stock_management_enabled', true),
                'is_active' => true,
                'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
                'properties' => $this->faker->optional(0.3)->randomElement([null, ['material' => $this->faker->word, 'warranty' => '1 year']]),
            ];
        }

        /**
         * Indicate that the product belongs to a specific shop.
         */
        public function forShop(Shop $shop)
        {
            return $this->state(['shop_id' => $shop->id]);
        }

        /**
         * Configure the factory to add categories to the product after creation.
         *
         * @param \Illuminate\Support\Collection|array|int $categories Category instance(s), ID(s), or count
         * @return static
         */
        public function withCategories($categories = 1)
        {
            return $this->afterCreating(function (Product $product) use ($categories) {
                if (is_numeric($categories)) {
                    // Create N new categories if not already existing or get random ones
                    $categoryIds = \Ijideals\CatalogManager\Models\Category::factory()->count($categories)->create()->pluck('id');
                } elseif ($categories instanceof \Illuminate\Support\Collection || is_array($categories)) {
                    $categoryIds = collect($categories)->map(function ($category) {
                        return $category instanceof \Ijideals\CatalogManager\Models\Category ? $category->id : $category;
                    })->all();
                } else {
                    return;
                }
                $product->categories()->sync($categoryIds);
            });
        }

        // TODO: Add states for `withOptions` and `withVariants` later if needed for complex seeding
        // These would typically involve the VariantGeneratorService or direct variant creation.
    }
