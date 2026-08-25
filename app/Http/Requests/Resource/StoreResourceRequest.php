<?php

namespace App\Http\Requests\Resource;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceRequest extends FormRequest
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
            'redirect' => ['nullable', 'string'],

            'node_id' => ['required', 'integer', 'exists:nodes,id'],
            'resource_type' => ['required', 'in:note,pdf,image,video'],
            'title' => ['required', 'string', 'max:100', 'min:2'],
            'content' => ['nullable', 'string'],

            'file' => [
                'nullable',
                'file',
                'max:10000',
                'mimes:jpg,jpeg,png',
                Rule::requiredIf($this->resource_type === 'image'),
            ],

            'external_url' => [
                'nullable',

                'url',
                'max:2048',
                Rule::requiredIf(in_array($this->resource_type, ['pdf', 'video'])),
            ],
        ];
    }
}
