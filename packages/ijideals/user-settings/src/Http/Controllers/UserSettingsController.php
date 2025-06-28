<?php

namespace Ijideals\UserSettings\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class UserSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the authenticated user's settings.
     * Can be filtered by providing a 'keys' query parameter (comma-separated).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->has('keys')) {
            $requestedKeys = explode(',', $request->input('keys'));
            $settings = [];
            foreach ($requestedKeys as $key) {
                $key = trim($key);
                // Only return keys that are defined in defaults to prevent fishing for arbitrary data
                if (Arr::has(config('user-settings.defaults', []), $key) || array_key_exists($key, config('user-settings.defaults', []))) {
                    $settings[$key] = $user->getSetting($key);
                } else {
                    // Optionally, include a notice for invalid keys or just ignore them
                    // $settings[$key] = ['error' => 'Undefined setting key'];
                }
            }
        } else {
            $settings = $user->getAllSettings();
        }

        return response()->json($settings);
    }

    /**
     * Update the specified settings for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $newSettings = $request->all();
        $allowedKeys = array_keys(Arr::dot(config('user-settings.defaults', []))); // Get all defined keys, including nested

        $updateData = [];
        foreach ($newSettings as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                // TODO: Add more specific validation based on the setting type if needed
                // For example, if 'notifications.new_like.database' must be boolean.
                // This could come from a more detailed config structure for 'defaults'.
                $updateData[$key] = $value;
            } else {
                // Log or ignore attempt to set an undefined key
                // Log::warning("[UserSettings] User {$user->id} attempted to update undefined setting key '{$key}'.");
            }
        }

        if (empty($updateData)) {
            return response()->json(['message' => 'No valid settings provided for update.'], 422);
        }

        $user->setSettings($updateData);

        return response()->json(['message' => 'Settings updated successfully.', 'settings' => $user->getAllSettings()]);
    }
}
