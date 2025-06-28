<?php

use Illuminate\Support\Facades\Route;
use IJIDeals\SocialPosts\Http\Controllers\PostController;

/*
|--------------------------------------------------------------------------
| API Routes for SocialPosts Package
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your package. These
| routes are loaded by the SocialPostsServiceProvider.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1/social')->group(function () {
    // Route pour récupérer tous les posts (peut-être paginés à l'avenir)
    Route::get('/posts', [PostController::class, 'index'])->name('socialposts.posts.index');

    // Route pour créer un nouveau post
    Route::post('/posts', [PostController::class, 'store'])->name('socialposts.posts.store');

    // Route pour récupérer un post spécifique
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('socialposts.posts.show');

    // Route pour mettre à jour un post existant
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('socialposts.posts.update');

    // Route pour supprimer un post existant
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('socialposts.posts.destroy');

    // Peut-être des routes pour les posts d'un utilisateur spécifique plus tard
    // Route::get('/users/{user}/posts', [PostController::class, 'indexByUser'])->name('socialposts.user.posts.index');
});
