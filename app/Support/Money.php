<?php

namespace App\Support;

final class Money {
    public static function toCents(string|float|int|null $value): int
    {
        if ($value == null || $value == '') {
            return 0;
        }

        if (is_string($value)) {
            $value = str_replace([',', ' ', '$'], ['.', '', ''], $value);
        }

        return (int) round(((float) $value) * 100);
    }

    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function display (int $cents): string
    {
        return '$' . number_format($cents / 100, 2, '.', ',');
    }

    public static function percent (int $cents, int|float $pct): int
    {
        return (int) round($cents * $pct / 100);
    }

    public static function withMargin (int $costCents, int|float $marginPct): int
    {
        return (int) round($costCents * (1 + $marginPct / 100));
    }
}