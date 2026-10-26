<?php

namespace App\Http\Requests\Resource;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Optional target subject and node IDs for resource re-assignment
            'subject_id' => ['sometimes', 'nullable', 'integer', 'exists:subjects,id'],
            'node_id' => ['sometimes', 'nullable', 'integer', 'exists:nodes,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'resource_type' => ['required', 'string', 'in:note,lecture_sheet,practical,marked_book,other,video'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
            'items.*.format' => ['required', 'in:image,pdf,video'],
            'items.*.source' => ['required', 'string'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            ResourceValidationRules::validateItems($validator, $this->input('items', []), $this->input('resource_type'));
        });
    }
}
