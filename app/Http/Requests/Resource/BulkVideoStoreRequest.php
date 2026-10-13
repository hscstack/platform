<?php

namespace App\Http\Requests\Resource;

use App\Models\Node;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkVideoStoreRequest extends FormRequest
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
            'playlist_url' => ['required', 'url'],
            'naming_strategy' => ['required', 'in:youtube,serial,prefix'],
            'start_number' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:naming_strategy,serial,prefix',
            ],
            'naming_prefix' => [
                'nullable',
                'string',
                'max:255',
            ],
            'is_reversed' => ['nullable', 'boolean'],
            'node_id' => ['required', 'exists:nodes,id'],
        ];
    }
}
