<?php

namespace Ijideals\UserSettings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory; // Optional, if you plan to create a factory for it

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        // 'value' will be cast dynamically by the HasSettings trait based on config
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('user-settings.table_name', 'user_settings');
    }

    /**
     * The user that this setting belongs to.
     */
    public function user(): BelongsTo
    {
        // Assumes the User model is App\Models\User or configured elsewhere (e.g., in auth config)
        // For package flexibility, it's better to use a configurable user model.
        // However, this model is primarily accessed via the User model's HasSettings trait,
        // so direct usage might be minimal.
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
    }

    /**
     * Set the value attribute.
     * If the value is an array or object, it will be JSON encoded.
     * Booleans will be stored appropriately if the HasSettings trait handles casting.
     *
     * @param mixed $value
     * @return void
     */
    public function setValueAttribute($value): void
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
        } elseif (is_bool($value)) {
            // Store booleans as 1 or 0 for database compatibility across systems,
            // casting will handle conversion back to boolean.
            $this->attributes['value'] = $value ? '1' : '0';
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Get the value attribute.
     * If the stored value is JSON, it will be decoded.
     * Casting to specific types (bool, int) will be handled by HasSettings trait
     * based on the 'casts' array in config/user-settings.php.
     *
     * @param string|null $value
     * @return mixed
     */
    public function getValueAttribute($value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        // The HasSettings trait will handle further casting to boolean/integer if defined in config.
        return $value;
    }

    // Optional: If you create a factory for this model
    protected static function newFactory()
    {
       return \Ijideals\UserSettings\Database\Factories\UserSettingFactory::new();
    }
}
