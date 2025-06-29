<?php

namespace Ijideals\SocialPosts\Http\Controllers;

use App\Http\Controllers\Controller; // Contrôleur de base de Laravel
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Ijideals\SocialPosts\Models\Post; // Modèle Post de notre package - Corrected

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        // TODO: Ajouter la logique pour récupérer et retourner les posts (par exemple, paginés)
        // Pour l'instant, retourne tous les posts, ce qui n'est pas idéal pour de grandes quantités.
        // Charger aussi les hashtags
        $posts = Post::with(['author', 'hashtags'])->latest()->paginate(15); // Ajout de paginate pour de meilleures perfs
        return response()->json($posts);
    }

    /**
     * Store a newly created resource in storage.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'content' => 'required|string|max:1000', // Exemple de validation
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        try {
            $post = $user->posts()->create([
                'content' => $validatedData['content'],
            ]);

            // Recharger le post avec l'auteur pour la réponse
            $post->load('author');

            // Sync hashtags from content
            if (method_exists($post, 'syncHashtags')) {
                $post->syncHashtags($validatedData['content']);
                $post->load('hashtags'); // Load hashtags for the response
            }

            return response()->json($post, 201); // 201 Created
        } catch (\Exception $e) {
            // Log the error for debugging
            // Log::error('Error creating post: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'controller_error_message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * @param \Ijideals\SocialPosts\Models\Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Post $post): JsonResponse
    {
        // Charger la relation author si ce n'est pas déjà fait par le route model binding
        // Charger aussi les hashtags
        $post->load(['author', 'hashtags']);
        return response()->json($post);
    }

    /**
     * Update the specified resource in storage.
     * @param \Illuminate\Http\Request $request
     * @param \Ijideals\SocialPosts\Models\Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Vérifier si l'utilisateur authentifié est l'auteur du post
        if ($post->author_id !== $currentUser->getKey() || $post->author_type !== get_class($currentUser)) {
            // Debugging information
            $debugInfo = [
                'message' => 'Unauthorized to update this post.',
                'post_author_id' => $post->author_id,
                'current_user_id' => $currentUser->getKey(),
                'post_author_type' => $post->author_type,
                'current_user_class' => get_class($currentUser),
                'id_comparison' => $post->author_id !== $currentUser->getKey(),
                'type_comparison' => $post->author_type !== get_class($currentUser),
            ];
            return response()->json($debugInfo, 403);
        }

        $validatedData = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $post->update($validatedData);

        // Recharger le post avec l'auteur pour la réponse
        $post->load('author');

        // Sync hashtags from content
        if (method_exists($post, 'syncHashtags')) {
            $post->syncHashtags($validatedData['content']);
            $post->load('hashtags'); // Load hashtags for the response
        }

        return response()->json($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): JsonResponse
    {
        // Explicitly reload the post
        $reloadedPost = Post::find($post->getKey());
        if (!$reloadedPost) {
            return response()->json(['message' => 'Post not found during reload.'], 404);
        }
        $post = $reloadedPost; // Use the reloaded instance

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Vérifier si l'utilisateur authentifié est l'auteur du post
        if ($post->author_id !== $currentUser->getKey() || $post->author_type !== get_class($currentUser)) {
            // Debugging information (similar to update)
            $debugInfo = [
                'message' => 'Unauthorized to delete this post.',
                'post_author_id' => $post->author_id,
                'current_user_id' => $currentUser->getKey(),
                'post_author_type' => $post->author_type,
                'current_user_class' => get_class($currentUser),
            ];
            return response()->json($debugInfo, 403);
        }

        $post->delete();

        return response()->json(null, 204); // 204 No Content
    }
}
