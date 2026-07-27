<?php

namespace App\Http\Requests\Resource;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkVideoStoreRequest extends FormRequest
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
            'redirect' => 'required|url',
            'playlist_url' => ['required', 'url'],
            'naming_strategy' => ['required', 'in:youtube,serial,prefix'],
            'start_number' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:naming_strategy,serial,prefix'
            ],
            'naming_prefix' => [
                'nullable',
                'string',
                'max:255',
                'required_if:naming_strategy,prefix'
            ],
            'node_id' => ['required', 'exists:nodes,id'],
        ];
    }
}
