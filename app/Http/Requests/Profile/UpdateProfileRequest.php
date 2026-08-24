<?php

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('password')) {
            $this->replace($this->except('password'));
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'file' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'about' => ['sometimes', 'nullable', 'string'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'institution' => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook' => ['sometimes', 'nullable', 'string', 'max:255'],
            'instagram' => ['sometimes', 'nullable', 'string', 'max:255'],
            'github' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
