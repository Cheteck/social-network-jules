<?php

namespace Ijideals\UserProfile\Concerns;

use Ijideals\UserProfile\Models\UserProfile;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasProfile
{
    /**
     * Get the profile associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\Ijideals\UserProfile\Models\UserProfile>
     */
    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id', $this->getKeyName());
    }

    /**
     * Get the user's profile. If it doesn't exist, create it.
     * This ensures a user always has a profile record.
     *
     * Access this as a dynamic property: $user->profile
     * Or as a method call: $user->profile() to get the builder, then ->first() or ->updateOrCreate() etc.
     * For simplicity and to always return a model (even if new and empty), we can use an accessor.
     *
     * @return \Ijideals\UserProfile\Models\UserProfile
     */
    public function getProfileAttribute(): UserProfile
    {
        // Check if relation is already loaded
        if ($this->relationLoaded('userProfile')) {
            $profile = $this->getRelation('userProfile');
            if ($profile) {
                return $profile;
            }
        }

        // Try to get it from database, or create a new one if it does not exist.
        // Using firstOrNew to avoid saving it immediately if we just want to read (potentially empty) attributes.
        // If an update is performed on this new instance, it will be saved.
        // Or use firstOrCreate if we want to ensure it's persisted right away.
        // For most use cases, firstOrCreate is better.
        $profile = $this->userProfile()->firstOrCreate(
            ['user_id' => $this->getKey()] // Ensure user_id is set if creating
        );

        // Set the relation on the model if it wasn't loaded or was null
        if (!$this->relationLoaded('userProfile') || !$this->getRelation('userProfile')) {
            $this->setRelation('userProfile', $profile);
        }

        return $profile;
    }

    // Optional: Direct accessors/mutators for profile fields from User model
    // Example for 'bio':
    // public function getBioAttribute(): ?string
    // {
    //     return $this->profile->bio;
    // }

    // public function setBioAttribute(?string $value): void
    // {
    //     $this->profile->bio = $value;
    //     $this->profile->save(); // Ensure profile is saved
    // }
}
