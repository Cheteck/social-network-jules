<?php

namespace Ijideals\ShopManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Ijideals\ShopManager\Models\Shop;
use Ijideals\SocialPosts\Models\Post; // Assuming this is the Post model used

class ShopPostController extends Controller
{
    protected $shopModelClass;
    protected $postModelClass;

    public function __construct()
    {
        $this->middleware('auth:api'); // All actions require authentication
        $this->shopModelClass = config('shop-manager.shop_model', Shop::class);
        $this->postModelClass = config('shop-manager.post_model_class', Post::class); // From shop-manager config
    }

    protected function findShop(string $shopSlugOrId): ?Shop
    {
        $query = $this->shopModelClass::query();
        if (is_numeric($shopSlugOrId)) {
            return $query->withDefault(fn () => null)->find((int)$shopSlugOrId);
        }
        return $query->where('slug', $shopSlugOrId)->first();
    }

    /**
     * Store a newly created post for the shop.
     */
    public function store(Request $request, string $shopSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        $this->authorize('createShopPost', $shop); // Uses ShopPolicy@createShopPost

        $validatedData = $request->validate([
            'content' => 'required|string|max:5000', // Max length from social-posts or define here
            // Add other post-specific validation if needed (e.g., media)
        ]);

        // The createPost method on Shop model handles author_id and author_type
        $post = $shop->createPost($validatedData);

        if ($post) {
            // TODO: Use API Resource for post transformation
            return response()->json($post, 201);
        }
        return response()->json(['message' => 'Failed to create shop post.'], 500);
    }

    /**
     * Display a listing of the shop's posts.
     */
    public function index(Request $request, string $shopSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        // Anyone can view a shop's posts (if shop is active, controller for shop handles that)
        // $this->authorize('view', $shop); // To ensure shop itself is viewable

        $posts = $shop->posts() // Using the relationship defined in Shop model
                       ->orderBy('created_at', 'desc')
                       ->paginate(config('social-posts.pagination_items', 15)); // Use pagination from social-posts if available

        // TODO: Use API Resource for post transformation
        return response()->json($posts);
    }
}
