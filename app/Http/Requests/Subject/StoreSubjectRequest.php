<?php

namespace App\Http\Requests\Subject;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'group' => $this->filled('group') ? $this->group : 'science',
            'sort_order' => $this->filled('sort_order') ? (int) $this->sort_order : 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'min:3'],
            'sort_order' => ['sometimes', 'integer'],
            'course' => ['required', 'string', 'in:ssc,hsc'],
            'group' => ['sometimes', 'string', 'in:science,humanities,commerce,common'],
            'is_optional' => ['sometimes', 'boolean'],
            'is_disabled' => ['sometimes', 'boolean'],
        ];
    }
}
