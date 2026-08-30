<?php

namespace App\Http\Requests\Resource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'node_id' => ['required', 'integer', 'exists:nodes,id'],
            'resource_type' => ['required', 'in:note,pdf,image,video'],
            'title' => ['required', 'string', 'max:100', 'min:2'],
            'content' => ['nullable', 'string'],

            'file' => [
                'nullable',
                'file',
                'max:10000',
                'mimes:jpg,jpeg,png',
                Rule::requiredIf(
                    $this->resource_type === 'image'
                        && ! $this->route('resource')->file_path
                ),
            ],

            'external_url' => [
                'nullable',
                'url',
                'max:2048',
                Rule::requiredIf(
                    in_array($this->resource_type, ['pdf', 'video'])
                ),
            ],
        ];
    }
}
