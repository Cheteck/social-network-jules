<?php

namespace Ijideals\UserProfile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only authenticated users can update their own profile.
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|string|url|max:255', // Validate as URL
            'location' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date_format:Y-m-d|before_or_equal:today', // Ensure it's a valid date and not in the future
            // Rules for avatar and cover photo will be added later
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.url' => 'The website must be a valid URL (e.g., http://example.com).',
            'birth_date.date_format' => 'The birth date must be in YYYY-MM-DD format.',
            'birth_date.before_or_equal' => 'The birth date cannot be in the future.',
        ];
    }
}
