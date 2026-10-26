<?php

namespace App\Http\Requests\Node;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNodeRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200', 'min:2'],
            'slug' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['sometimes', 'integer'],
            'is_trackable' => ['sometimes', 'boolean'],
            'weight' => ['sometimes', 'integer', 'in:1,2,3'],
        ];
    }
}
