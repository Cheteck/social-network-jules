<?php

namespace Ijideals\ShopManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Ijideals\ShopManager\Models\Shop; // As per your config
use App\Models\User; // As per your config, likely App\Models\User

class ShopController extends Controller
{
    protected $shopModelClass;
    protected $userModelClass;

    public function __construct()
    {
        $this->middleware('auth:api')->except(['index', 'show']); // Public listing and viewing
        $this->shopModelClass = config('shop-manager.shop_model', Shop::class);
        $this->userModelClass = config('shop-manager.user_model', User::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // TODO: Add filtering (e.g., by owner, by status, search term)
        $shops = $this->shopModelClass::where('is_active', true)
            ->with('owner') // Eager load owner
            // ->withCount('followers') // If shops become followable
            ->paginate(config('shop-manager.pagination_items', 15));

        // TODO: Use API Resource for transformation
        return response()->json($shops);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', $this->shopModelClass); // Authorize policy for creating

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:' . app($this->shopModelClass)->getTable() . ',name',
            'description' => 'nullable|string|max:5000',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:30',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2', // ISO 3166-1 alpha-2
            'is_active' => 'sometimes|boolean',
            // 'settings' => 'sometimes|array' // If you allow direct settings input
            'logo' => 'sometimes|image|max:'.(config('media-uploader.collections.default.max_file_size', 5120)), // Max size from media-uploader default or specific collection
            'cover_image' => 'sometimes|image|max:'.(config('media-uploader.collections.default.max_file_size', 5120)),
        ]);

        $user = Auth::user();
        $shop = $user->shopsOwned()->create($validatedData);

        // Assign 'shop_owner' role to the owner for this shop.
        // This assumes 'shop_owner' is defined in RolesAndPermissionsSeeder and is typically the first in default_shop_roles.
        $ownerRoleName = config('shop-manager.permission.default_shop_roles.0', 'shop_owner');

        $roleInstance = app(\Spatie\Permission\Contracts\Role::class)->findByName($ownerRoleName, $user->guard_name);
        if ($roleInstance) {
            // Detach any other shop-specific roles first to ensure only one primary role if that's the logic
            // $user->roles()->wherePivot(config('permission.column_names.team_foreign_key'), $shop->id)->detach();
            $user->roles()->attach($roleInstance->id, [config('permission.column_names.team_foreign_key') => $shop->id]);
            $user->forgetCachedPermissions();
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoCollectionName = config('shop-manager.media_collections.shop_logo.name', 'shop_logo');
            $shop->addMedia($request->file('logo'), $logoCollectionName);
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $coverCollectionName = config('shop-manager.media_collections.shop_cover_image.name', 'shop_cover_image');
            $shop->addMedia($request->file('cover_image'), $coverCollectionName);
        }

        // TODO: Use API Resource for transformation
        return response()->json($shop->load(['owner', 'media']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slugOrId)
    {
        $shop = $this->findShop($slugOrId);
        if (!$shop || !$shop->is_active) {
             // Allow admins/owners to see inactive shops? Gate check needed if so.
            return response()->json(['message' => 'Shop not found or not active.'], 404);
        }
        // TODO: Use API Resource for transformation
        return response()->json($shop->load(['owner', 'media'])); // Eager load media (logo, cover)
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slugOrId)
    {
        $shop = $this->findShop($slugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        $this->authorize('update', $shop); // Authorize using ShopPolicy@update

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:' . $shop->getTable() . ',name,' . $shop->id,
            'description' => 'nullable|string|max:5000',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:30',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',
            'is_active' => 'sometimes|boolean',
            // 'settings' => 'sometimes|array'
            'logo' => 'sometimes|image|max:'.(config('media-uploader.collections.default.max_file_size', 5120)),
            'cover_image' => 'sometimes|image|max:'.(config('media-uploader.collections.default.max_file_size', 5120)),
        ]);

        $shop->update(Arr::except($validatedData, ['logo', 'cover_image']));

        // Handle logo upload (replaces if single_file collection)
        if ($request->hasFile('logo')) {
            $logoCollectionName = config('shop-manager.media_collections.shop_logo.name', 'shop_logo');
            $shop->addMedia($request->file('logo'), $logoCollectionName); // addMedia handles single file replacement
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $coverCollectionName = config('shop-manager.media_collections.shop_cover_image.name', 'shop_cover_image');
            $shop->addMedia($request->file('cover_image'), $coverCollectionName);
        }

        // TODO: Use API Resource for transformation
        return response()->json($shop->load(['owner', 'media']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slugOrId)
    {
        $shop = $this->findShop($slugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        $this->authorize('delete', $shop); // Authorize using ShopPolicy@delete

        // Media associated will be deleted by HasMedia trait on Shop model if it cascades.
        // Roles associated via model_has_roles will be orphaned if not cleaned up (Spatie doesn't auto-clean on team model delete).
        // Consider a ShopDeleting event to clean up Spatie roles for this shop_id.
        $shop->delete();

        return response()->json(['message' => 'Shop deleted successfully.'], 200);
    }

    /**
     * Helper to find a shop by slug or ID.
     */
    protected function findShop(string $slugOrId): ?Shop
    {
        $query = $this->shopModelClass::query();
        if (is_numeric($slugOrId)) {
            return $query->withDefault(fn () => null)->find((int)$slugOrId); // Return null if not found
        }
        return $query->where('slug', $slugOrId)->first();
    }

    // Method for handling logo/cover uploads could be part of update or separate
    public function uploadShopLogo(Request $request, string $shopSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) return response()->json(['message' => 'Shop not found.'], 404);

        $this->authorize('uploadShopMedia', $shop);

        $request->validate(['logo' => 'required|image|max:'.(config('media-uploader.collections.default.max_file_size', 5120))]);

        $logoCollectionName = config('shop-manager.media_collections.shop_logo.name', 'shop_logo');
        $shop->addMedia($request->file('logo'), $logoCollectionName);

        return response()->json(['message' => 'Shop logo uploaded successfully.', 'logo_url' => $shop->refresh()->logo_url]);
    }

    public function uploadShopCover(Request $request, string $shopSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) return response()->json(['message' => 'Shop not found.'], 404);

        $this->authorize('uploadShopMedia', $shop);

        $request->validate(['cover_image' => 'required|image|max:'.(config('media-uploader.collections.default.max_file_size', 5120))]);

        $coverCollectionName = config('shop-manager.media_collections.shop_cover_image.name', 'shop_cover_image');
        $shop->addMedia($request->file('cover_image'), $coverCollectionName);

        return response()->json(['message' => 'Shop cover image uploaded successfully.', 'cover_image_url' => $shop->refresh()->cover_image_url]);
    }
}
