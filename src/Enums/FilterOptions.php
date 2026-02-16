<?php

declare(strict_types=1);

namespace Almani\Xero\Enums;

final class FilterOptions
{
    public const Ids             = 'ids';
    public const IncludeArchived = 'includeArchived';
    public const Order           = 'order';
    public const Page            = 'page';
    public const SearchTerm      = 'searchTerm';
    public const SummaryOnly     = 'summaryOnly';
    public const Where           = 'where';
    public const Statuses        = 'Statuses';

    /**
     * Check if value is a valid filter option
     */
    public static function isValid(string $value): bool
    {
        $validValues = [
            self::Ids,
            self::IncludeArchived,
            self::Order,
            self::Page,
            self::SearchTerm,
            self::SummaryOnly,
            self::Where,
            self::Statuses,
        ];

        return in_array($value, $validValues, true);
    }
}
