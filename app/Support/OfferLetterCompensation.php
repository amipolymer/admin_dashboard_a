<?php

namespace App\Support;

class OfferLetterCompensation
{
    public const RETENTION_SUFFIX = 'Annual Retention Award to be paid after successful completion of 01 year and shall be part of total annual CTC';

    public const VARIABLE_SUFFIX = 'Variable Pay';

    /**
     * @return array{ctc: float, fixed: float, retention: ?float, variable: ?float}
     */
    public static function breakdown(array $offer): array
    {
        $ctc = max(0, (float) ($offer['ctc'] ?? 0));
        $retention = OnboardingLetterData::positiveAmount($offer['retention_bonus'] ?? null) ?? 0.0;
        $variable = OnboardingLetterData::positiveAmount($offer['variable_component'] ?? null) ?? 0.0;
        $fixed = max(0, $ctc - $retention - $variable);

        return [
            'ctc' => $ctc,
            'fixed' => $fixed,
            'retention' => $retention > 0 ? $retention : null,
            'variable' => $variable > 0 ? $variable : null,
        ];
    }

    public static function rupee(float $amount): string
    {
        if ($amount <= 0) {
            return '₹0';
        }

        return '₹' . IndianAmountFormat::indianNumberFormat((int) round($amount));
    }

    public static function hasComponentBreakdown(array $offer): bool
    {
        $c = self::breakdown($offer);

        return $c['retention'] !== null || $c['variable'] !== null;
    }

    public static function validateOfferAmounts(float $ctc, mixed $retention, mixed $variable): ?string
    {
        if ($ctc <= 0) {
            return 'Annual CTC must be greater than zero.';
        }

        $retentionAmount = OnboardingLetterData::positiveAmount($retention) ?? 0.0;
        $variableAmount = OnboardingLetterData::positiveAmount($variable) ?? 0.0;

        if ($retentionAmount + $variableAmount > $ctc) {
            return 'Retention bonus and variable pay together cannot exceed total annual CTC.';
        }

        return null;
    }

    public static function letterHtml(array $offer): string
    {
        $comp = self::breakdown($offer);
        $ctcFormatted = IndianAmountFormat::annualCtc($comp['ctc']);
        $html = 'Your annual compensation for this role will be <span class="strong">'
            . '₹' . e($ctcFormatted['amount'])
            . ' (' . e($ctcFormatted['words']) . ')'
            . '</span>';

        if (self::hasComponentBreakdown($offer)) {
            $html .= ' out of which <span class="strong">'
                . e(self::rupee($comp['fixed']))
                . ' (Fixed)</span>';

            if ($comp['retention'] !== null) {
                $html .= ' + <span class="strong">'
                    . e(self::rupee($comp['retention']))
                    . ' (' . e(self::RETENTION_SUFFIX) . ')</span>';
            }

            if ($comp['variable'] !== null) {
                $html .= ' + <span class="strong">'
                    . e(self::rupee($comp['variable']))
                    . ' (' . e(self::VARIABLE_SUFFIX) . ')</span>';
            }
        }

        return $html . '.';
    }
}
