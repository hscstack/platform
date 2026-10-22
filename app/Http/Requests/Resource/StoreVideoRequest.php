<?php

namespace App\Http\Requests\Resource;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'node_id' => ['required', 'integer', 'exists:nodes,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'playlist_url' => ['required', 'url'],
        ];
    }
}
