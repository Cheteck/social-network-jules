<?php

namespace Ijideals\HashtagSystem\Http\Controllers;

use Ijideals\HashtagSystem\Models\Hashtag;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class HashtagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $hashtags = Hashtag::query()->orderBy('name')->paginate(20);
        return response()->json($hashtags);
    }

    /**
     * Display the specified resource.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $hashtag = Hashtag::where('slug', $slug)->firstOrFail();
        return response()->json($hashtag);
    }

    /**
     * Get posts associated with a specific hashtag.
     *
     * Note: This method assumes that the Post model (or other models)
     * will use the HasHashtags trait and define a relationship.
     * The actual Post model is in a different package, so this is a placeholder
     * for how the interaction would occur.
     *
     * @param string $slug The slug of the hashtag.
     * @param Request $request
     * @return JsonResponse
     */
    public function getPostsByHashtag(string $slug, Request $request): JsonResponse
    {
        $hashtag = Hashtag::where('slug', $slug)->firstOrFail();

        // We assume a 'posts' relationship exists on the Hashtag model,
        // dynamically created by __call or explicitly defined if Post model was accessible.
        // Example: $hashtag->posts()
        // Since we cannot directly access the Post model from 'ijideals/social-posts' package right now,
        // we rely on the dynamic relationship established in Hashtag::__call or a similar mechanism.
        // If the 'Post' model was available, it would look something like:
        // $posts = $hashtag->morphedByMany(\Ijideals\SocialPosts\Models\Post::class, 'hashtaggable')->paginate();

        if (method_exists($hashtag, 'posts')) {
            $posts = $hashtag->posts()->paginate(); // 'posts' should be the relation name
            return response()->json($posts);
        }

        // Fallback or error if the relationship isn't defined (e.g. Post model not integrated yet)
        return response()->json(['message' => 'Posts for this hashtag are not available at the moment.'], 404);
    }

     /**
     * Get items of a specific type associated with a specific hashtag.
     *
     * @param string $slug The slug of the hashtag.
     * @param string $type The type of items to retrieve (e.g., 'post', 'product').
     * @param Request $request
     * @return JsonResponse
     */
    public function getItemsByHashtagAndType(string $slug, string $type, Request $request): JsonResponse
    {
        $hashtag = Hashtag::where('slug', $slug)->firstOrFail();

        $modelName = Str::studly($type);
        $modelClassApp = "App\\Models\\{$modelName}";
        // This is a simplified list. A more robust solution might involve a configuration file
        // mapping types to their fully qualified model class names.
        $knownPackageModels = [
            'Post' => 'Ijideals\\SocialPosts\\Models\\Post',
            // Add other known models from your packages here
            // 'Product' => 'Ijideals\\CatalogManager\\Models\\Product',
        ];

        $modelFQN = null;
        if (class_exists($modelClassApp)) {
            $modelFQN = $modelClassApp;
        } elseif (isset($knownPackageModels[$modelName]) && class_exists($knownPackageModels[$modelName])) {
            $modelFQN = $knownPackageModels[$modelName];
        }

        if (!$modelFQN) {
            return response()->json(['message' => "The type '{$type}' is not recognized or its model class could not be found."], 400);
        }

        // Check if the model uses the HasHashtags trait (indirectly, by checking for the relation)
        // A direct check like `uses_trait($modelFQN, HasHashtags::class)` would be better if classes were loaded.
        // For now, we rely on the relationship defined in Hashtag model's __call or explicit methods.
        $relationName = Str::plural(Str::camel($type)); // e.g., 'posts', 'products'

        if (method_exists($hashtag, $relationName)) {
            $items = $hashtag->{$relationName}()->paginate();
            return response()->json($items);
        }

        return response()->json(['message' => "{$type} for this hashtag are not available or the relationship '{$relationName}' is not defined on the Hashtag model."], 404);
    }
}
