<?php

namespace Ijideals\CatalogManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Ijideals\ShopManager\Models\Shop;
use Ijideals\CatalogManager\Models\Product;
use Ijideals\CatalogManager\Models\ProductVariant;
use Ijideals\CatalogManager\Services\VariantGeneratorService; // If using the service
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;

class ProductVariantController extends Controller
{
    protected $shopModelClass;
    protected $productModelClass;
    protected $variantModelClass;

    public function __construct()
    {
        $this->middleware('auth:api')->except(['index']); // Public listing of variants for a product

        $this->shopModelClass = config('catalog-manager.shop_model', Shop::class);
        $this->productModelClass = config('catalog-manager.product_model', Product::class);
        $this->variantModelClass = config('catalog-manager.product_variant_model', ProductVariant::class);
    }

    protected function findShop(string $shopSlugOrId): ?Shop
    {
        $query = $this->shopModelClass::query();
        return is_numeric($shopSlugOrId) ? $query->find((int)$shopSlugOrId) : $query->where('slug', $shopSlugOrId)->first();
    }

    protected function findProductInShop(Shop $shop, string $productSlugOrId): ?Product
    {
        $query = $shop->products();
        return is_numeric($productSlugOrId) ? $query->find((int)$productSlugOrId) : $query->where('slug', $productSlugOrId)->first();
    }

    /**
     * List all variants for a given product.
     */
    public function index(Request $request, string $shopSlugOrId, string $productSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) return response()->json(['message' => 'Shop not found.'], 404);

        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product) return response()->json(['message' => 'Product not found.'], 404);

        $query = $product->variants()->where('is_active', true); // Only active variants by default for public view

        // TODO: Allow filtering by option values if needed

        $variants = $query->with(['media', 'optionValues.option']) // Eager load images and option values with their parent option
            ->paginate(config('catalog-manager.pagination_items.variants', 15)); // New config key

        return response()->json($variants);
    }

    /**
     * Store a newly created product variant.
     */
    public function store(Request $request, string $shopSlugOrId, string $productSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) return response()->json(['message' => 'Shop not found.'], 404);
        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product) return response()->json(['message' => 'Product not found.'], 404);

        // Authorization check
        if (Auth::user()->cannot('manageVariants', $product)) { // Example permission on ProductPolicy
             if(!Auth::user()->hasShopRole(['shop_admin', 'shop_editor'], $shop) && Auth::id() !== $shop->owner_id) {
                return response()->json(['message' => 'Unauthorized to manage variants for this product.'], 403);
             }
        }

        $validated = $request->validate([
            'sku' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique(app($this->variantModelClass)->getTable())->where('product_id', $product->id)],
            'price' => 'sometimes|numeric|min:0', // Absolute price for variant
            'price_modifier' => 'sometimes|nullable|numeric', // Or modifier
            'stock_quantity' => config('catalog-manager.stock_management_enabled', true) ? 'required|integer|min:0' : 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'option_value_ids' => 'required|array', // Array of ProductOptionValue IDs that define this variant
            'option_value_ids.*' => 'required|integer|exists:'.app(config('catalog-manager.product_option_value_model'))->getTable().',id',
            'images' => 'sometimes|array|max:3', // Max 3 images per variant for now
            'images.*' => 'image|max:'.(config('media-uploader.collections.default.max_file_size', 5120)),
        ]);

        // Ensure combination of option_value_ids is unique for this product
        $existingVariant = app(VariantGeneratorService::class)->findExistingVariant($product, $validated['option_value_ids']);
        if ($existingVariant) {
            return response()->json(['message' => 'A variant with this combination of options already exists.'], 409);
        }

        $variantData = Arr::except($validated, ['option_value_ids', 'images']);
        $variantData['product_id'] = $product->id;

        $variant = $this->variantModelClass::create($variantData);
        $variant->optionValues()->sync($validated['option_value_ids']);

        if ($request->hasFile('images')) {
            $imageCollectionName = config('catalog-manager.media_collections.product_variant_images.name', 'product_variant_images'); // New collection
            foreach ($request->file('images') as $imageFile) {
                $variant->addMedia($imageFile, $imageCollectionName);
            }
        }

        return response()->json($variant->load(['optionValues.option', 'media']), 201);
    }

    /**
     * Generate multiple variants based on product's options.
     */
    public function generateVariants(Request $request, string $shopSlugOrId, string $productSlugOrId, VariantGeneratorService $generator)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) return response()->json(['message' => 'Shop not found.'], 404);
        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product) return response()->json(['message' => 'Product not found.'], 404);

        // Authorization
        if (Auth::user()->cannot('manageVariants', $product)) {
             if(!Auth::user()->hasShopRole(['shop_admin', 'shop_editor'], $shop) && Auth::id() !== $shop->owner_id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
             }
        }

        // Default data for all generated variants, can be overridden by request
        $defaultVariantData = $request->validate([
            'default_stock_quantity' => 'sometimes|integer|min:0',
            'default_price_modifier' => 'sometimes|nullable|numeric',
            'activate_new_variants' => 'sometimes|boolean',
        ]);

        $variantsDataForCreation = [
            'stock_quantity' => $defaultVariantData['default_stock_quantity'] ?? 0,
            'price_modifier' => $defaultVariantData['default_price_modifier'] ?? null,
            'is_active' => $defaultVariantData['activate_new_variants'] ?? true,
        ];

        $generatedVariants = $generator->generate($product, true, $variantsDataForCreation);

        return response()->json([
            'message' => count($generatedVariants) . ' variants processed (created or existing).',
            'variants' => $generatedVariants->load(['optionValues.option', 'media'])
        ]);
    }


    /**
     * Display the specified product variant.
     */
    public function show(string $shopSlugOrId, string $productSlugOrId, int $variantId)
    {
        // Similar logic to ProductController::show but for a variant
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) return response()->json(['message' => 'Shop not found.'], 404);
        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product) return response()->json(['message' => 'Product not found.'], 404);

        $variant = $product->variants()->with(['media', 'optionValues.option'])->find($variantId);
        if (!$variant || !$variant->is_active) { // Also check product's active status?
            return response()->json(['message' => 'Product variant not found or not active.'], 404);
        }
        return response()->json($variant);
    }

    /**
     * Update the specified product variant.
     */
    public function update(Request $request, string $shopSlugOrId, string $productSlugOrId, int $variantId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) return response()->json(['message' => 'Shop not found.'], 404);
        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product) return response()->json(['message' => 'Product not found.'], 404);
        $variant = $product->variants()->find($variantId);
        if (!$variant) return response()->json(['message' => 'Product variant not found.'], 404);

        // Authorization
        if (Auth::user()->cannot('manageVariants', $product)) {
             if(!Auth::user()->hasShopRole(['shop_admin', 'shop_editor'], $shop) && Auth::id() !== $shop->owner_id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
             }
        }

        $validated = $request->validate([
            'sku' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique($variant->getTable())->where('product_id', $product->id)->ignore($variant->id)],
            'price' => 'sometimes|numeric|min:0',
            'price_modifier' => 'sometimes|nullable|numeric',
            'stock_quantity' => config('catalog-manager.stock_management_enabled', true) ? 'sometimes|required|integer|min:0' : 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'option_value_ids' => 'sometimes|array', // Updating options for a variant can be complex, usually means creating a new variant
            'option_value_ids.*' => 'integer|exists:'.app(config('catalog-manager.product_option_value_model'))->getTable().',id',
        ]);

        // If option_value_ids are changed, it's essentially a new variant.
        // For MVP, we might disallow changing option_value_ids directly on update.
        // Or, if they change, we check if the new combination already exists.
        if (isset($validated['option_value_ids'])) {
            $newCombinationIds = $validated['option_value_ids'];
            sort($newCombinationIds);
            $currentCombinationIds = $variant->optionValues()->pluck('id')->toArray();
            sort($currentCombinationIds);

            if ($newCombinationIds !== $currentCombinationIds) {
                $existingVariantWithNewOptions = app(VariantGeneratorService::class)->findExistingVariant($product, $newCombinationIds);
                if ($existingVariantWithNewOptions && $existingVariantWithNewOptions->id !== $variant->id) {
                    return response()->json(['message' => 'Another variant with this combination of options already exists.'], 409);
                }
                 $variant->optionValues()->sync($newCombinationIds);
            }
        }

        $variant->update(Arr::except($validated, ['option_value_ids', 'images']));
        // TODO: Handle image updates for variants (add/remove)

        return response()->json($variant->load(['optionValues.option', 'media']));
    }

    /**
     * Remove the specified product variant.
     */
    public function destroy(string $shopSlugOrId, string $productSlugOrId, int $variantId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) return response()->json(['message' => 'Shop not found.'], 404);
        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product) return response()->json(['message' => 'Product not found.'], 404);
        $variant = $product->variants()->find($variantId);
        if (!$variant) return response()->json(['message' => 'Product variant not found.'], 404);

        // Authorization
        if (Auth::user()->cannot('manageVariants', $product)) {
             if(!Auth::user()->hasShopRole(['shop_admin', 'shop_editor'], $shop) && Auth::id() !== $shop->owner_id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
             }
        }

        $variant->delete(); // Media associated via HasMedia should also be handled (cascade or event)
        return response()->json(['message' => 'Product variant deleted successfully.']);
    }
}
