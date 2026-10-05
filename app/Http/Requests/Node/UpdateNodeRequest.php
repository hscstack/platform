<?php

namespace App\Http\Requests\Node;

use App\Models\Node;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $node = $this->route('node');
        if ($node?->isEffectivelyFrozen()) {
            return false;
        }

        if ($this->has('parent_id') && $this->input('parent_id') && (int) $this->input('parent_id') !== (int) $node?->parent_id) {
            $targetParent = Node::find($this->input('parent_id'));
            if ($targetParent?->isEffectivelyFrozen()) {
                return false;
            }
        }

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
            'name' => ['sometimes', 'required', 'string', 'max:200', 'min:2'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:200'],
            'parent_id' => ['sometimes', 'nullable', 'integer'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
            'is_trackable' => ['sometimes', 'boolean'],
            'weight' => ['sometimes', 'integer', 'in:1,2,3'],
        ];
    }
}
