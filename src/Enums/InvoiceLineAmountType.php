<?php

declare(strict_types=1);

namespace Almani\Xero\Enums;

final class InvoiceLineAmountType
{
    public const Exclusive = 'Exclusive';
    public const Inclusive = 'Inclusive';
    public const NoTax     = 'NoTax';

    /**
     * Check if the given value is a valid LineAmountType
     */
    public static function isValid(string $value): bool
    {
        $validValues = [
            self::Exclusive,
            self::Inclusive,
            self::NoTax,
        ];

        return in_array($value, $validValues, true);
    }
}
