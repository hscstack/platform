<?php

namespace App\Http\Requests\Resource;

use App\Models\Node;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $node = Node::find($this->input('node_id'));

        return ! ($node?->isEffectivelyFrozen());
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

            if ($format === 'image' && count($items) > 50) {
                $validator->errors()->add('items', 'একটি রিসোর্সে সর্বোচ্চ ৫০টি ছবি যুক্ত করা যাবে।');

                return;
            } elseif ($format === 'video' && count($items) > 50) {
                $validator->errors()->add('items', 'একটি প্লেলিস্ট/রিসোর্সে সর্বোচ্চ ৫০টি ভিডিও যুক্ত করা যাবে।');

                return;
            } elseif ($format === 'pdf' && count($items) > 5) {
                $validator->errors()->add('items', 'একটি রিসোর্সে সর্বোচ্চ ৫টি PDF ডকুমেন্ট যুক্ত করা যাবে।');

                return;
            }

            if ($type === 'video' && $format !== 'video') {
                $validator->errors()->add('items', 'ভিডিও রিসোর্সে শুধুমাত্র ভিডিও লিংক থাকতে পারে।');
            } elseif ($type !== 'video' && $format === 'video') {
                $validator->errors()->add('items', 'নোট বা ডকুমেন্টে ভিডিও লিংক যুক্ত করা যাবে না।');
            }

            $userId = auth()->id();
            foreach ($items as $index => $item) {
                $itemFormat = $item['format'] ?? null;
                $source = $item['source'] ?? '';

                if (in_array($itemFormat, ['pdf', 'video'], true)) {
                    if (! filter_var($source, FILTER_VALIDATE_URL) || (! str_starts_with($source, 'http://') && ! str_starts_with($source, 'https://'))) {
                        $validator->errors()->add("items.{$index}.source", 'PDF এবং ভিডিওর জন্য সঠিক URL লিঙ্ক প্রদান করতে হবে।');
                    }
                } elseif ($itemFormat === 'image') {
                    // Must match user's uploaded path format or a valid image URL
                    $expectedPrefix = 'resources/notes/'.$userId.'/';
                    $isUserUploaded = str_starts_with($source, $expectedPrefix) && preg_match('#^resources/notes/'.$userId.'/[0-9a-fA-F-]{36}\.(jpg|jpeg|png|webp)$#', $source);
                    $isExternalUrl = (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) && filter_var($source, FILTER_VALIDATE_URL);

                    if (! $isUserUploaded && ! $isExternalUrl) {
                        $validator->errors()->add("items.{$index}.source", 'ছবির জন্য একটি সঠিক আপলোড কী বা লিঙ্ক প্রদান করতে হবে।');
                    }
                }
            }
        });
    }
}
