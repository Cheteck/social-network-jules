<?php

namespace Ijideals\CatalogManager\Services;

use Ijideals\CatalogManager\Models\Product;
use Ijideals\CatalogManager\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

class VariantGeneratorService
{
    /**
     * Generate all possible variant combinations for a given product
     * based on its assigned product options and their values.
     *
     * @param Product $product
     * @param bool $createInDatabase If true, creates the variants in the DB. Otherwise, just returns potential data.
     * @param array $defaultVariantData Default data to apply to each new variant (e.g., stock_quantity, is_active).
     * @return Collection Collection of ProductVariant instances or arrays of data.
     */
    public function generate(Product $product, bool $createInDatabase = false, array $defaultVariantData = []): Collection
    {
        $options = $product->productOptions()->with('values')->get();

        if ($options->isEmpty()) {
            return new Collection();
        }

        // Prepare arrays of option values for Cartesian product
        $optionValueArrays = [];
        foreach ($options as $option) {
            // For MVP, we use all global values of an option type linked to the product.
            // Future: Could use a product-specific subset of these values if stored on pivot.
            $values = $product->getAllowedValuesForOption($option);
            if ($values->isNotEmpty()) {
                $optionValueArrays[] = $values->pluck('id')->all();
            }
        }

        if (empty($optionValueArrays)) {
            return new Collection(); // No values to combine
        }

        $combinations = $this->cartesianProduct($optionValueArrays);
        $generatedVariants = new Collection();

        foreach ($combinations as $combination) {
            // $combination is an array of ProductOptionValue IDs
            $variantData = $this->prepareVariantData($product, $combination, $defaultVariantData);

            if ($createInDatabase) {
                // Check if a variant with this exact combination of option values already exists
                $existingVariant = $this->findExistingVariant($product, $combination);
                if ($existingVariant) {
                    // Optionally update existing variant or skip
                    // For now, skip if exists to avoid duplicates from re-generation
                    $generatedVariants->push($existingVariant);
                    continue;
                }

                $variant = $product->variants()->create(Arr::except($variantData, ['option_value_ids']));
                $variant->optionValues()->sync($variantData['option_value_ids']);
                $generatedVariants->push($variant);
            } else {
                $generatedVariants->push($variantData);
            }
        }

        return $generatedVariants;
    }

    /**
     * Prepare data for a single variant.
     */
    protected function prepareVariantData(Product $product, array $optionValueIds, array $defaults): array
    {
        // Create a SKU based on product SKU (if exists) and option values
        // This is a simple SKU generation, can be customized.
        $skuParts = [];
        if ($product->sku) {
            $skuParts[] = $product->sku;
        }
        // Fetch option values to build a more descriptive SKU or name part
        $values = app(config('catalog-manager.product_option_value_model'))::findMany($optionValueIds);
        foreach ($values as $value) {
            $skuParts[] = Str::slug($value->value, ''); // e.g., RED, S, M
        }
        $generatedSku = implode('-', $skuParts);
        if(empty($generatedSku)) $generatedSku = $product->slug . '-variant-' . implode('-', $optionValueIds);


        return array_merge([
            'product_id' => $product->id,
            'sku' => $generatedSku,
            'price_modifier' => 0.00, // Default modifier
            'stock_quantity' => 0,
            'is_active' => true,
            'option_value_ids' => $optionValueIds, // For syncing relation
        ], $defaults);
    }

    /**
     * Find if a variant with the exact combination of option values already exists for the product.
     */
    protected function findExistingVariant(Product $product, array $targetOptionValueIds): ?ProductVariant
    {
        sort($targetOptionValueIds); // Ensure order for comparison

        return $product->variants()->whereHas('optionValues', function ($query) use ($targetOptionValueIds) {
            $query->whereIn('product_option_value_id', $targetOptionValueIds);
        }, '=', count($targetOptionValueIds)) // Must match all specified values
        ->whereDoesntHave('optionValues', function ($query) use ($targetOptionValueIds) {
            $query->whereNotIn('product_option_value_id', $targetOptionValueIds);
        }) // And must not have any other values
        ->first();
    }


    /**
     * Computes the Cartesian product of arrays.
     *
     * @param array $arrays An array of arrays.
     * @return array Cartesian product.
     */
    protected function cartesianProduct(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $key => $values) {
            $append = [];
            foreach ($result as $product) {
                foreach ($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }
            $result = $append;
        }
        return $result;
    }
}
