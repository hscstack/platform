<?php

namespace App\Http\Requests\User;

use App\Rules\CleanText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'username' => ['nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username', new CleanText],
            'email' => ['required', 'email', 'unique:users,email'],
            'is_verified' => ['sometimes', 'boolean'],
            'role' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'file' => ['nullable', 'image', 'max:2048'],
            'about' => ['nullable', 'string', 'max:1000', new CleanText],
            'title' => ['nullable', 'string', 'max:255', new CleanText],
            'institution' => ['nullable', 'string', 'max:255', new CleanText],
            'facebook' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'github' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'email_verified_at' => now(),
        ]);
    }
}
