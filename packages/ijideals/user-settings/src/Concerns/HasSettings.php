<?php

namespace Ijideals\UserSettings\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait HasSettings
{
    /**
     * Get all settings for the user.
     * This relationship fetches raw UserSetting model instances.
     */
    public function settings(): HasMany
    {
        $userSettingModel = config('user-settings.usersetting_model', \Ijideals\UserSettings\Models\UserSetting::class);
        return $this->hasMany($userSettingModel);
    }

    /**
     * Get a specific setting value for the user.
     *
     * @param string $key The setting key (e.g., 'notifications.new_like.database').
     * @param mixed $default Optional default value if the setting is not found.
     *                       If null, the default from config('user-settings.defaults') will be used.
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        $userSetting = $this->settings()->where('key', $key)->first();

        if ($userSetting) {
            return $this->castSettingValue($key, $userSetting->value);
        }

        // If no user-specific setting, use the default from config or the provided default
        $configDefault = Arr::get(config('user-settings.defaults', []), $key);

        return $this->castSettingValue($key, $default ?? $configDefault);
    }

    /**
     * Set a specific setting for the user.
     * Creates or updates the setting.
     *
     * @param string $key The setting key.
     * @param mixed $value The value to set.
     * @return \Ijideals\UserSettings\Models\UserSetting|false
     */
    public function setSetting(string $key, $value)
    {
        // Check if the key is an allowed setting key defined in defaults
        if (!Arr::has(config('user-settings.defaults', []), $key) && !array_key_exists($key, config('user-settings.defaults', []))) {
             Log::warning("[UserSettings] Attempted to set an undefined setting key '{$key}' for user {$this->id}.");
             // Depending on strictness, you might throw an exception or return false.
             // For now, let's allow it but log it. If you want to be strict, uncomment the return false.
             // return false;
        }

        return $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value] // The UserSetting model's mutator will handle JSON encoding if needed
        );
    }

    /**
     * Set multiple settings for the user.
     *
     * @param array $settingsArray An associative array of [key => value].
     * @return void
     */
    public function setSettings(array $settingsArray): void
    {
        foreach ($settingsArray as $key => $value) {
            $this->setSetting($key, $value);
        }
    }

    /**
     * Get all default settings defined in the configuration.
     *
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return config('user-settings.defaults', []);
    }

    /**
     * Get all settings for the user, merged with defaults.
     * User's specific settings override the defaults.
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        $defaultSettings = $this->getDefaultSettings();
        $userSpecificSettingsRaw = $this->settings()->pluck('value', 'key');

        $userSettings = [];
        foreach ($userSpecificSettingsRaw as $key => $rawValue) {
            $userSettings[$key] = $this->castSettingValue($key, $rawValue);
        }

        // Merge user settings on top of defaults. For nested arrays, simple array_merge might not be deep.
        // Arr::dot and then merge, then undot could be one way for deep merge.
        // For simplicity, let's use array_replace_recursive for deep merging.
        return array_replace_recursive($defaultSettings, $userSettings);
    }

    /**
     * Cast a setting value to its appropriate type based on config.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castSettingValue(string $key, $value)
    {
        $casts = config('user-settings.casts', []);
        $castType = Arr::get($casts, $key);

        if (is_null($value)) return null;

        switch ($castType) {
            case 'boolean':
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
            case 'int':
                return intval($value);
            case 'float':
            case 'double':
                return floatval($value);
            case 'string':
                return strval($value);
            case 'array':
                return is_array($value) ? $value : json_decode($value, true);
            case 'object':
                 return is_object($value) ? $value : json_decode($value, false);
            // Add other casts like 'date', 'datetime', 'collection' if needed
            default:
                // If value is already decoded array/object from UserSetting model, return it
                if(is_array($value) || is_object($value)) return $value;

                // Attempt to decode if it's a JSON string, otherwise return as is
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }
                }
                return $value;
        }
    }
}
