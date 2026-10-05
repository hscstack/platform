<?php

namespace App\Http\Requests\Resource;

use App\Models\Node;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $resource = $this->route('resource');
        if ($resource?->node?->isEffectivelyFrozen()) {
            return false;
        }

        if ($this->has('node_id') && (int) $this->input('node_id') !== (int) $resource?->node_id) {
            $targetNode = Node::find($this->input('node_id'));
            if ($targetNode?->isEffectivelyFrozen()) {
                return false;
            }
        }

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
                'max:5120',
                'mimes:jpg,jpeg,png,webp',
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
