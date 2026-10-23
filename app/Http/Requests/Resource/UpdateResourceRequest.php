<?php

namespace App\Http\Requests\Resource;

use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;

class UpdateResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $resource = $this->route('resource');
        if (! $resource instanceof Resource) {
            $resource = Resource::find($resource);
        }

        if (! $resource) {
            return false;
        }

        if ($resource->node?->isEffectivelyFrozen()) {
            return false;
        }

        $user = $this->user();

        return $user && ($user->can('edit resources') || $user->id === $resource->user_id);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'resource_type' => ['required', 'string', 'in:note,practical,marked_book,other,video'],
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
