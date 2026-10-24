<?php

namespace App\Http\Requests\Resource;

use App\Models\Node;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->filled('node_id')) {
            $node = Node::find($this->input('node_id'));

            return ! ($node?->isEffectivelyFrozen());
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'node_id' => [
                'nullable',
                'integer',
                'exists:nodes,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->filled('subject_id')) {
                        $nodeSubjectId = Node::where('id', $value)->value('subject_id');
                        if ($nodeSubjectId && (int) $nodeSubjectId !== (int) $this->input('subject_id')) {
                            $fail('নির্বাচিত অধ্যায়টি নির্বাচিত বিষয়ের অন্তর্ভুক্ত নয়।');
                        }
                    }
                },
            ],
            'resource_type' => ['required', 'string', 'in:note,practical,marked_book,other,video'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
