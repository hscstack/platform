<?php

namespace App\Http\Requests\Notice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNoticeRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'show_button' => ['required', 'boolean'],
            'button_title' => ['nullable', 'required_if:show_button,true', 'string', 'max:100'],
            'button_link' => ['nullable', 'required_if:show_button,true', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
