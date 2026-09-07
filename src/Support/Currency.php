<?php

declare(strict_types=1);

namespace Jengo\Pdf\Support;

class Currency
{
    /**
     * Common single-character or special currency symbols that are prefixed directly without a space.
     *
     * @var list<string>
     */
    protected static array $symbolPrefixes = [
        '$', '€', '£', '¥', '₹', '₽', '₩', '₺', '₴', '₱', '₲', '₡', '¢', '֏', '฿', '৳', '៛', '₦',
    ];

    /**
     * Formats an amount with currency symbol or ISO 4217 code.
     *
     * Standard rules:
     * - Single-character symbols (e.g. '$', '€', '£') attach directly: '$1,250.00', '€1,250.00'
     * - Multi-character ISO codes or abbreviations (e.g. 'KES', 'USD', 'EUR', 'GBP', 'Ksh', 'UGX', 'TZS', 'CAD', 'AUD', 'ZAR')
     *   are cleanly separated by a space: 'KES 1,250.00', 'USD 1,250.00'
     * - Negative numbers: '-$1,250.00', '-KES 1,250.00'
     * - Automatically sanitizes trailing/leading spaces from input currency so 'KES ' or ' KES' yields 'KES 1,250.00'.
     *
     * @param float|int|string|null $amount   Numeric amount or formatted number string
     * @param string|null           $currency Currency symbol or code (defaults to config or '$')
     * @param string|null           $locale   Optional locale (e.g. 'en_US', 'en_KE') for Intl NumberFormatter
     * @param int                   $decimals Number of decimal places (default: 2)
     */
    public static function format(
        mixed $amount,
        ?string $currency = null,
        ?string $locale = null,
        int $decimals = 2
    ): string {
        $currency = trim((string) ($currency ?? config('Pdf')?->templating['defaults']['currency'] ?? '$'));
        if ($currency === '') {
            $currency = '$';
        }

        $num = self::parse($amount);
        $isNegative = $num < 0;
        $absNum = abs($num);
        $formattedNum = number_format($absNum, $decimals);

        // Check if currency is a recognized direct symbol prefix
        if (in_array($currency, self::$symbolPrefixes, true)) {
            return ($isNegative ? '-' : '') . $currency . $formattedNum;
        }

        // For ISO codes (KES, USD, EUR, etc.) or multi-character abbreviations (Ksh, Rs, etc.), ensure single space
        return ($isNegative ? '-' : '') . $currency . ' ' . $formattedNum;
    }

    /**
     * Parses numeric value from float, int, or string with currency symbols / commas.
     */
    public static function parse(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = (string) $value;
        // Remove currency symbols, commas, and whitespace, keep minus and dot
        $cleaned = preg_replace('/[^\d.-]/', '', str_replace(',', '', $str));

        return ($cleaned !== '' && $cleaned !== null && is_numeric($cleaned)) ? (float) $cleaned : 0.0;
    }
}
