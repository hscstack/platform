<?php

namespace App\Http\Requests\Resource;

use App\Models\Node;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $nodeId = $this->input('node_id');
        if ($nodeId) {
            $node = Node::find($nodeId);
            if ($node && $node->isEffectivelyFrozen()) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'node_id' => ['required', 'integer', 'exists:nodes,id'],
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
            $items = $this->input('items', []);
            if (empty($items)) {
                return;
            }

            $formats = array_unique(array_column($items, 'format'));
            if (count($formats) > 1) {
                $validator->errors()->add('items', 'একটি রিসোর্সে একাধিক ফরম্যাট (ছবি, PDF, ভিডিও) একসাথে রাখা যাবে না।');

                return;
            }

            $format = $formats[0] ?? null;
            $type = $this->input('resource_type');

            if ($type === 'video' && $format !== 'video') {
                $validator->errors()->add('items', 'ভিডিও রিসোর্সে শুধুমাত্র ভিডিও লিংক থাকতে পারে।');
            } elseif ($type !== 'video' && $format === 'video') {
                $validator->errors()->add('items', 'নোট বা ডকুমেন্টে ভিডিও লিংক যুক্ত করা যাবে না।');
            }
        });
    }
}
