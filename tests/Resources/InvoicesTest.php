<?php

declare(strict_types=1);

use Almani\Xero\Facades\Xero;
use Almani\Xero\Resources\Invoices;

test('invalid filter option throws exception', function () {
    Xero::invoices()
        ->filter('bogus', 1)
        ->get();
})->throws(InvalidArgumentException::class, "Filter option 'bogus' is not valid.");

test('filter returns object', function () {

    $filter = (new Invoices)->filter('ids', '1234');

    expect($filter)->toBeObject();
});
