<?php

namespace App\Http\Requests\Resource;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkImageStoreRequest extends FormRequest
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
            //
            'redirect' => 'required|url',
            'node_id' => 'required|exists:nodes,id',
            'custom_titles' => 'required|array|max:20',
            'custom_titles.*' => 'required|string|max:100',
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'required|image|max:10240', // 10MB Limit
        ];
    }
}
