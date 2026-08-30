<?php

namespace App\Rules;

use App\Services\ChatProfanityFilter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CleanText implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && trim($value) !== '' && ChatProfanityFilter::hasProfanity($value)) {
            $attributeName = str_replace('_', ' ', $attribute);
            $fail("The {$attributeName} contains inappropriate or prohibited language.");
        }
    }
}
