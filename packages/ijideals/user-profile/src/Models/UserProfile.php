<?php

namespace Ijideals\UserProfile\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User; // Assuming the main app's User model

/**
 * Class UserProfile
 * @package Ijideals\UserProfile\Models
 *
 * @property int $user_id
 * @property string|null $bio
 * @property string|null $website
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property-read \App\Models\User $user
 */
class UserProfile extends Model
{
    use HasFactory; // We might create a factory for this later

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'bio',
        'website',
        'location',
        'birth_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get the user that owns the profile.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Ijideals\UserProfile\Database\Factories\UserProfileFactory
     */
    protected static function newFactory()
    {
        return \Ijideals\UserProfile\Database\Factories\UserProfileFactory::new();
    }
}
