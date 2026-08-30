<?php

namespace App\Services;

use App\Models\AppSetting;

class ChatProfanityFilter
{
    /**
     * Check if the given text contains abusive or banned words.
     * Returns true if profanity is detected, false otherwise.
     */
    public static function hasProfanity(string $text): bool
    {
        $enabled = AppSetting::get('global_chat_profanity_filter_enabled', true);
        if (! $enabled) {
            return false;
        }

        $bannedWords = self::getBannedWords();
        if (empty($bannedWords)) {
            return false;
        }

        $normalized = self::normalize($text);

        // 1. Remove punctuation separators within words (e.g. "f.u.c.k", "s_e_x", "a-s-s")
        $strippedPunctuation = preg_replace('/(?<=\p{L})[._\-*~]+(?=\p{L})/u', '', $normalized);

        // 2. Collapse spaced-out single letters (e.g. "f u c k" -> "fuck", "a s s" -> "ass")
        $collapsedSpaces = preg_replace_callback('/\b(?:\p{L}\s+){2,}\p{L}\b/u', function ($m) {
            return preg_replace('/\s+/u', '', $m[0]);
        }, $strippedPunctuation);

        $variants = [$text, $normalized, $strippedPunctuation, $collapsedSpaces];

        foreach ($bannedWords as $word) {
            $word = trim(mb_strtolower($word));
            if ($word === '') {
                continue;
            }

            $escaped = preg_quote($word, '/');
            // Unicode-safe word boundary pattern to prevent substring false-positives (e.g. "ass" in "assalamualaikum")
            $pattern = '/(?<=^|[^\p{L}\p{N}])'.$escaped.'(?=[^\p{L}\p{N}]|$)/ui';

            foreach ($variants as $variant) {
                if (preg_match($pattern, $variant)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Mask/Censor message with a system notice if abusive or prohibited language is detected.
     */
    public static function maskProfanity(string $text, string $notice = '[Message hidden for inappropriate language]'): string
    {
        if (self::hasProfanity($text)) {
            return $notice;
        }

        return $text;
    }

    /**
     * Normalize text by replacing leet-speak substitutions and collapsing repeated characters.
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);

        // Common leet-speak substitutions
        $substitutions = [
            '@' => 'a',
            '4' => 'a',
            '$' => 's',
            '5' => 's',
            '1' => 'i',
            '!' => 'i',
            '|' => 'i',
            '0' => 'o',
            '3' => 'e',
            '8' => 'b',
            '+' => 't',
            '7' => 't',
        ];

        $text = strtr($text, $substitutions);

        // Collapse repeated characters: e.g. "fuuuck" -> "fuck", "biiiitch" -> "bitch"
        $text = preg_replace('/(.)\1{2,}/u', '$1', $text);

        return $text;
    }

    /**
     * Get the active list of banned words from AppSetting.
     *
     * @return array<int, string>
     */
    public static function getBannedWords(): array
    {
        $customWords = AppSetting::get('global_chat_banned_words', '');

        if (empty($customWords)) {
            return [];
        }

        if (is_array($customWords)) {
            return $customWords;
        }

        // Split by commas or newlines
        $words = preg_split('/[\r\n,]+/', (string) $customWords);

        return array_values(array_filter(array_map('trim', $words), fn ($w) => $w !== ''));
    }
}
