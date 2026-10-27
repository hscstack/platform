<?php

namespace App\Http\Requests\Profile;

use App\Rules\CleanText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new CleanText],
            'username' => [
                'sometimes',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($this->user()->id),
                new CleanText,
            ],
            'file' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'clear_image' => ['sometimes', 'boolean'],
            'about' => ['sometimes', 'nullable', 'string', 'max:255', new CleanText],
            'institution' => ['sometimes', 'nullable', 'string', 'max:255', new CleanText],
            'activity_privacy' => ['sometimes', 'string', Rule::in(['public', 'appreciators_only', 'private'])],
            'batch' => ['sometimes', 'nullable', 'string', 'max:32'],
            'allow_pokes' => ['sometimes', 'boolean'],
            'facebook' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'url',
                'starts_with:https://',
                'regex:/^https:\/\/(www\.)?(facebook|fb)\.com\/[a-zA-Z0-9._\-]+\/?$/i',
            ],
            'instagram' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'url',
                'starts_with:https://',
                'regex:/^https:\/\/(www\.)?instagram\.com\/[a-zA-Z0-9._\-]+\/?$/i',
            ],
            'github' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'url',
                'starts_with:https://',
                'regex:/^https:\/\/(www\.)?github\.com\/[a-zA-Z0-9._\-]+\/?$/i',
            ],
            'website' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'url',
                'starts_with:https://,http://',
            ],
            'receive_emails' => ['sometimes', 'boolean'],
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
            'username.regex' => "Username can only contain letters, numbers, and underscores. Dots aren't allowed.",
            'username.unique' => 'This username is already taken. Please choose another one.',
            'facebook.regex' => 'Please enter a valid Facebook profile URL (e.g., https://facebook.com/username).',
            'facebook.url' => 'Please enter a valid URL starting with https://.',
            'instagram.regex' => 'Please enter a valid Instagram profile URL (e.g., https://instagram.com/username).',
            'instagram.url' => 'Please enter a valid URL starting with https://.',
            'github.regex' => 'Please enter a valid GitHub profile URL (e.g., https://github.com/username).',
            'github.url' => 'Please enter a valid URL starting with https://.',
            'website.url' => 'Please enter a valid website URL starting with https:// or http://.',
        ];
    }
}
