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

        foreach ($bannedWords as $word) {
            $word = trim(mb_strtolower($word));
            if ($word === '') {
                continue;
            }

            // Exact word boundary matching or normalized substring matching
            $escaped = preg_quote($word, '/');
            if (preg_match("/\b{$escaped}\b/ui", $text) || preg_match("/\b{$escaped}\b/ui", $normalized)) {
                return true;
            }

            // Compact match without spaces or special symbols (e.g. f.u.c.k or f_u_c_k)
            $compactText = preg_replace('/[^\p{L}\p{N}]/u', '', $normalized);
            $compactWord = preg_replace('/[^\p{L}\p{N}]/u', '', $word);
            if ($compactWord !== '' && str_contains($compactText, $compactWord)) {
                return true;
            }
        }

        return false;
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
