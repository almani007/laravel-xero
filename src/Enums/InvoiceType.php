<?php

declare(strict_types=1);

namespace Almani\Xero\Enums;

final class InvoiceType
{
    public const AccPay = 'ACCPAY';
    public const AccRec = 'ACCREC';

    /**
     * Validate if a given value is a valid InvoiceType
     */
    public static function isValid(string $value): bool
    {
        $validValues = [
            self::AccPay,
            self::AccRec,
        ];

        return in_array($value, $validValues, true);
    }
}
