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
        $node = $this->route('node');

        return ! ($node?->is_frozen);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:200', 'min:2'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:200'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
            'is_trackable' => ['sometimes', 'boolean'],
            'weight' => ['sometimes', 'integer', 'in:1,2,3'],
        ];
    }
}
