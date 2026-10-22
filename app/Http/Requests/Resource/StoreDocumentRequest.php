<?php

namespace App\Http\Requests\Resource;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'node_id' => ['required', 'integer', 'exists:nodes,id'],
            'resource_type' => ['required', 'in:note,practical,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:format,source,title'],
            'items.*.format' => ['required', 'in:image,pdf'],
            'items.*.source' => ['required', 'string'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
