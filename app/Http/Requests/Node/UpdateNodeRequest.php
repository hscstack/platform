<?php

namespace App\Http\Requests\Node;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNodeRequest extends FormRequest
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
            'name'       => ['sometimes', 'required', 'string', 'max:200', 'min:2'],
            'slug'       => ['sometimes', 'nullable', 'string', 'max:200'],
            'parent_id'  => ['sometimes', 'nullable', 'integer'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
            'redirect'   => ['nullable', 'string'],
        ];
    }
}
