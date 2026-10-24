<?php

namespace App\Http\Requests\Resource;

use Illuminate\Validation\Validator;

class ResourceValidationRules
{
    public static function validateItems(Validator $validator, array $items, ?string $resourceType): void
    {
        if (empty($items)) {
            return;
        }

        $formats = array_unique(array_column($items, 'format'));
        if (count($formats) > 1) {
            $validator->errors()->add('items', 'একটি রিসোর্সে একাধিক ফরম্যাট (ছবি, PDF, ভিডিও) একসাথে রাখা যাবে না।');

            return;
        }

        $format = $formats[0] ?? null;

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

        if ($resourceType === 'video' && $format !== 'video') {
            $validator->errors()->add('items', 'ভিডিও রিসোর্সে শুধুমাত্র ভিডিও লিংক থাকতে পারে।');
        } elseif ($resourceType !== 'video' && $format === 'video') {
            $validator->errors()->add('items', 'নোট বা ডকুমেন্টে ভিডিও লিংক যুক্ত করা যাবে না।');
        }

        foreach ($items as $index => $item) {
            $itemFormat = $item['format'] ?? null;
            $source = $item['source'] ?? '';

            if (in_array($itemFormat, ['pdf', 'video'], true)) {
                if (! filter_var($source, FILTER_VALIDATE_URL) || (! str_starts_with($source, 'http://') && ! str_starts_with($source, 'https://'))) {
                    $validator->errors()->add("items.{$index}.source", 'PDF এবং ভিডিওর জন্য সঠিক URL লিঙ্ক প্রদান করতে হবে।');
                }
            } elseif ($itemFormat === 'image') {
                if (! is_string($source) || ! str_starts_with($source, 'resources/')) {
                    $validator->errors()->add("items.{$index}.source", 'ছবির জন্য একটি সঠিক আপলোড কী প্রদান করতে হবে।');
                }
            }
        }
    }
}
