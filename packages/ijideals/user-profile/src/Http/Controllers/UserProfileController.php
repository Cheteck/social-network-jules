<?php

namespace Ijideals\UserProfile\Http\Controllers;

use App\Models\User; // Main application User model
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller; // Base controller
use Illuminate\Support\Facades\Auth;
use Ijideals\UserProfile\Http\Requests\UpdateUserProfileRequest;
use Ijideals\UserProfile\Models\UserProfile; // The package's UserProfile model

class UserProfileController extends Controller
{
    /**
     * Display the specified user's profile.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        // The HasProfile trait's accessor $user->profile will ensure profile exists
        return response()->json($user->profile);
    }

    /**
     * Display the authenticated user's own profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function current(): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser) {
            // This case should ideally be handled by auth middleware, but as a safeguard:
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        // The HasProfile trait's accessor $authUser->profile will ensure profile exists
        return response()->json($authUser->profile);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Ijideals\UserProfile\Http\Requests\UpdateUserProfileRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser) {
             // This case should ideally be handled by auth middleware
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // The $request->validated() will contain only the fields defined in rules and validated.
        $validatedData = $request->validated();

        // The accessor $authUser->profile ensures the profile model instance exists.
        // Use Eloquent's update for mass assignment and saving.
        $profile = $authUser->profile;
        $profile->update($validatedData);

        return response()->json($profile->fresh()); // Return the fresh model to reflect DB state
    }
}
