<?php

namespace App\Http\Requests\Chapter;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChapterRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:200', 'min:2'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
            'weight' => ['sometimes', 'integer', 'in:1,2,3'],
        ];
    }
}
