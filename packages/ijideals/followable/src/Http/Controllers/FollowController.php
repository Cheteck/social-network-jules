<?php

namespace Ijideals\Followable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
// Assuming the User model is in App\Models\User
// Adjust the namespace if your User model is located elsewhere.
use App\Models\User;

class FollowController extends Controller
{
    /**
     * Follow a user.
     *
     * @param User $user The user to follow.
     * @return JsonResponse
     */
    public function follow(User $user): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->isFollowing($user)) {
            return response()->json(['message' => 'You are already following this user.'], 422);
        }

        if ($currentUser->getKey() === $user->getKey()) {
            return response()->json(['message' => 'You cannot follow yourself.'], 422);
        }

        $currentUser->follow($user);

        return response()->json(['message' => 'Successfully followed the user.']);
    }

    /**
     * Unfollow a user.
     *
     * @param User $user The user to unfollow.
     * @return JsonResponse
     */
    public function unfollow(User $user): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->isFollowing($user)) {
            return response()->json(['message' => 'You are not following this user.'], 422);
        }

        $currentUser->unfollow($user);

        return response()->json(['message' => 'Successfully unfollowed the user.']);
    }

    /**
     * Toggle follow status for a user.
     *
     * @param User $user The user to toggle follow status for.
     * @return JsonResponse
     */
    public function toggleFollow(User $user): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->getKey() === $user->getKey()) {
            return response()->json(['message' => 'Action not allowed on yourself.'], 422);
        }

        $currentUser->toggleFollow($user);
        $message = $currentUser->isFollowing($user) ? 'Successfully followed the user.' : 'Successfully unfollowed the user.';

        return response()->json(['message' => $message]);
    }

    /**
     * Check if the current user is following a target user.
     *
     * @param User $user The user to check.
     * @return JsonResponse
     */
    public function isFollowing(User $user): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $isFollowing = $currentUser->isFollowing($user);

        return response()->json(['is_following' => $isFollowing]);
    }

    /**
     * Get the list of followers for a user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function followers(User $user): JsonResponse
    {
        // The `followers` relationship on the User model (via Followable trait)
        // returns a collection of User models that are following the specified $user.
        return response()->json($user->followers()->get());
    }

    /**
     * Get the list of users a user is following.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function followings(User $user): JsonResponse
    {
        // The `followings` relationship on the User model (via Followable trait)
        // returns a collection of User models that the specified $user is following.
        return response()->json($user->followings()->get());
    }
}
