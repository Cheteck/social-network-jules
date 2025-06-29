<?php

namespace Ijideals\CatalogManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Ijideals\ShopManager\Models\Shop; // From ShopManager package
use Ijideals\CatalogManager\Models\Product; // This package's Product model
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;

class ProductController extends Controller
{
    protected $productModelClass;
    protected $shopModelClass;
    protected $categoryModelClass;

    public function __construct()
    {
        // Public viewing of products is allowed for index and show
        // Other actions require authentication and authorization (shop admin/editor)
        $this->middleware('auth:api')->except(['index', 'show']);

        $this->productModelClass = config('catalog-manager.product_model', Product::class);
        $this->shopModelClass = config('catalog-manager.shop_model', Shop::class);
        $this->categoryModelClass = config('catalog-manager.category_model', \Ijideals\CatalogManager\Models\Category::class);
    }

    protected function findShop(string $shopSlugOrId): ?Shop
    {
        $query = $this->shopModelClass::query();
        if (is_numeric($shopSlugOrId)) {
            return $query->find((int)$shopSlugOrId);
        }
        return $query->where('slug', $shopSlugOrId)->first();
    }


    /**
     * Display a listing of products for a specific shop.
     */
    public function index(Request $request, string $shopSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop || !$shop->is_active) {
            return response()->json(['message' => 'Shop not found or not active.'], 404);
        }

        $query = $shop->products()->where('is_active', true); // Use the relationship

        // TODO: Add filtering (by category, price range, etc.) and sorting
        if ($request->has('category')) {
            $categorySlugOrId = $request->input('category');
            $category = app($this->categoryModelClass)->where('slug', $categorySlugOrId)->orWhere('id', $categorySlugOrId)->first();
            if ($category) {
                $query->whereHas('categories', fn($q) => $q->where(app($this->categoryModelClass)->getTable().'.id', $category->id));
            }
        }

        $products = $query->with(['media', 'categories']) // Eager load images and categories
            ->paginate(config('catalog-manager.pagination_items.products', 15));

        // TODO: Use API Resource for transformation
        return response()->json($products);
    }

    /**
     * Store a newly created product in storage for a specific shop.
     */
    public function store(Request $request, string $shopSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        // Authorization: User must be shop_admin or shop_editor for this shop
        if (Auth::user()->cannot('createProductInShop', $shop)) {
             // Gate::authorize('createProductInShop', $shop); // Using a policy/gate
             // Or direct check:
             if(!Auth::user()->hasShopRole(['shop_admin', 'shop_editor'], $shop) && Auth::id() !== $shop->owner_id) {
                 return response()->json(['message' => 'Unauthorized to create products for this shop.'], 403);
             }
        }


        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            // Slug will be auto-generated based on name, unique within the shop
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique(app($this->productModelClass)->getTable(), 'sku')->where('shop_id', $shop->id)],
            'stock_quantity' => config('catalog-manager.stock_management_enabled', true) ? 'required|integer|min:0' : 'nullable|integer|min:0',
            'manage_stock' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'properties' => 'sometimes|array',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'integer|exists:'.app($this->categoryModelClass)->getTable().',id',
            'images' => 'sometimes|array|max:5', // Max 5 images per upload for now
            'images.*' => 'image|max:'.(config('media-uploader.collections.default.max_file_size', 5120)), // Validate each image
        ]);

        $productData = Arr::except($validatedData, ['category_ids', 'images']);
        $productData['shop_id'] = $shop->id; // Ensure shop_id is set

        $product = $this->productModelClass::create($productData);

        if (isset($validatedData['category_ids'])) {
            $product->categories()->sync($validatedData['category_ids']);
        }

        if ($request->hasFile('images')) {
            $imageCollectionName = config('catalog-manager.media_collections.product_images.name', 'product_images');
            foreach ($request->file('images') as $imageFile) {
                $product->addMedia($imageFile, $imageCollectionName);
            }
        }

        // TODO: Use API Resource
        return response()->json($product->load(['shop', 'categories', 'media']), 201);
    }

    /**
     * Display the specified product from a specific shop.
     */
    public function show(string $shopSlugOrId, string $productSlugOrId) // Product slug or ID
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop || !$shop->is_active) {
            return response()->json(['message' => 'Shop not found or not active.'], 404);
        }

        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product || !$product->is_active) {
            // Allow shop admins/editors to see inactive products of their shop?
            // if(!$product || (!$product->is_active && Auth::user()->cannot('viewInactiveProducts', $shop))) {}
            return response()->json(['message' => 'Product not found or not active.'], 404);
        }

        // TODO: Use API Resource
        return response()->json($product->load(['shop', 'categories', 'media']));
    }

    /**
     * Update the specified product in storage for a specific shop.
     */
    public function update(Request $request, string $shopSlugOrId, string $productSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        // Authorization
        if (Auth::user()->cannot('updateProductInShop', [$shop, $product])) {
             if(!Auth::user()->hasShopRole(['shop_admin', 'shop_editor'], $shop) && Auth::id() !== $shop->owner_id) {
                return response()->json(['message' => 'Unauthorized to update this product.'], 403);
             }
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'sku' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique($product->getTable(), 'sku')->where('shop_id', $shop->id)->ignore($product->id)],
            'stock_quantity' => config('catalog-manager.stock_management_enabled', true) ? 'sometimes|required|integer|min:0' : 'nullable|integer|min:0',
            'manage_stock' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'properties' => 'sometimes|array',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'integer|exists:'.app($this->categoryModelClass)->getTable().',id',
            // Image updates are often handled by separate endpoints (add image, delete image, reorder)
        ]);

        $product->update(Arr::except($validatedData, ['category_ids']));

        if (isset($validatedData['category_ids'])) {
            $product->categories()->sync($validatedData['category_ids']);
        }

        // TODO: Handle image updates (e.g., adding new ones, deleting old ones if needed via separate requests or more complex logic here)

        return response()->json($product->load(['shop', 'categories', 'media']));
    }

    /**
     * Remove the specified product from storage for a specific shop.
     */
    public function destroy(string $shopSlugOrId, string $productSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        $product = $this->findProductInShop($shop, $productSlugOrId);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        // Authorization
        if (Auth::user()->cannot('deleteProductInShop', [$shop, $product])) {
            if(!Auth::user()->hasShopRole(['shop_admin', 'shop_editor'], $shop) && Auth::id() !== $shop->owner_id) {
                return response()->json(['message' => 'Unauthorized to delete this product.'], 403);
            }
        }

        $product->delete(); // Media will be handled by HasMedia trait if cascade is set up on Media model

        return response()->json(['message' => 'Product deleted successfully.'], 200);
    }

    protected function findProductInShop(Shop $shop, string $slugOrId): ?Product
    {
        $query = $shop->products(); // Query through the shop relationship
        if (is_numeric($slugOrId)) {
            return $query->find((int)$slugOrId);
        }
        return $query->where('slug', $slugOrId)->first();
    }

}
