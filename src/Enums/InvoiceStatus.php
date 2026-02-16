<?php

declare(strict_types=1);

namespace Almani\Xero\Enums;

final class InvoiceStatus
{
    public const Authorised = 'AUTHORISED';
    public const Deleted    = 'DELETED';
    public const Draft      = 'DRAFT';
    public const Paid       = 'PAID';
    public const Submitted  = 'SUBMITTED';
    public const Voided     = 'VOIDED';

    /**
     * Validate if a given value is a valid InvoiceStatus
     */
    public static function isValid(string $value): bool
    {
        $validValues = [
            self::Authorised,
            self::Deleted,
            self::Draft,
            self::Paid,
            self::Submitted,
            self::Voided,
        ];

        return in_array($value, $validValues, true);
    }
}
