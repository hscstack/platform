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
            'about' => ['sometimes', 'nullable', 'string', 'max:1000', new CleanText],
            'institution' => ['sometimes', 'nullable', 'string', 'max:255', new CleanText],
            'activity_privacy' => ['sometimes', 'string', Rule::in(['public', 'appreciators_only', 'private'])],
            'allow_pokes' => ['sometimes', 'boolean'],
            'facebook' => ['sometimes', 'nullable', 'string', 'max:255'],
            'instagram' => ['sometimes', 'nullable', 'string', 'max:255'],
            'github' => ['sometimes', 'nullable', 'string', 'max:255'],
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
        ];
    }
}
