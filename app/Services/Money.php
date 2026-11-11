<?php

namespace App\Services;

use InvalidArgumentException;

final class Money
{
    public static function toCents(string|int $value): int
    {
        $value = trim((string) $value);

        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Invalid peso amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function fromCents(int $cents): string
    {
        if ($cents < 0 || $cents > 9999999999) {
            throw new InvalidArgumentException('Money amount is outside the supported range.');
        }

        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
