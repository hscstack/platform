<?php

namespace App\Http\Requests\Resource;

use App\Models\Node;
use Illuminate\Auth\Access\AuthorizationException;
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
        $node = Node::find($this->input('node_id'));

        if ($node?->isEffectivelyFrozen() && ! $this->user()?->can('moderate resources')) {
            return false;
        }

        return true;
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('এই ফোল্ডারটি লক করা রয়েছে। অ্যাডমিন কর্তৃক কন্টেন্ট আপলোড সাময়িকভাবে বন্ধ আছে।');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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
