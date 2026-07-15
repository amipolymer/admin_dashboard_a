<?php

namespace App\Support;

class OnboardingLetterRejectReason
{
    public const PLAIN_TEXT_REGEX = '/^[\p{L}\p{N}\s]+$/u';

    public static function passesQualityCheck(string $reason): bool
    {
        $text = trim($reason);
        if ($text === '') {
            return false;
        }

        if (preg_match('/^(.)\1+$/u', $text)) {
            return false;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) < 2) {
            return false;
        }

        $substantiveWords = 0;
        foreach ($words as $word) {
            if (preg_match('/^(.)\1+$/u', $word)) {
                return false;
            }
            if (mb_strlen($word) >= 2) {
                $substantiveWords++;
            }
        }

        return $substantiveWords >= 2;
    }

    /** @return list<string|\Closure> */
    public static function rules(): array
    {
        return [
            'required',
            'string',
            'min:10',
            'max:1000',
            'regex:' . self::PLAIN_TEXT_REGEX,
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (!self::passesQualityCheck((string) $value)) {
                    $fail('Please enter a proper reason with at least two words. Repeated letters only (e.g. BBBBBBBBBBB) are not allowed.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for declining.',
            'reason.min' => 'Reason must be at least 10 characters.',
            'reason.regex' => 'Reason may only contain letters, numbers, and spaces (no special characters).',
        ];
    }
}
