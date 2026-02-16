<?php

declare(strict_types=1);

namespace Almani\Xero\Enums;

final class ContactStatus
{
    public const Active      = 'ACTIVE';
    public const Archived    = 'ARCHIVED';
    public const GDPRRequest = 'GDPRREQUEST';

    /**
     * Check if a value is valid contact status
     */
    public static function isValid(string $value): bool
    {
        $validValues = [
            self::Active,
            self::Archived,
            self::GDPRRequest,
        ];

        return in_array($value, $validValues, true);
    }
}
