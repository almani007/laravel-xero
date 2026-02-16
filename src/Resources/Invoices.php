<?php

declare(strict_types=1);

namespace Almani\Xero\Resources;

use Almani\Xero\Enums\FilterOptions;
use Almani\Xero\Xero;
use InvalidArgumentException;

class Invoices extends Xero
{
    protected array $queryString = [];

    /**
     * Apply a filter to the query string.
     *
     * @param string $key
     * @param string|int $value
     * @return self
     */
    public function filter(string $key, $value): self
    {
        if (! FilterOptions::isValid($key)) {
            throw new InvalidArgumentException("Filter option '$key' is not valid.");
        }

        $this->queryString[$key] = $value;

        return $this;
    }

    public function get(): array
    {
        $queryString = $this->formatQueryStrings($this->queryString);

        $result = parent::get('Invoices?' . $queryString);

        return $result['body']['Invoices'];
    }

    public function find(string $invoiceId): array
    {
        $result = parent::get('Invoices/' . $invoiceId);
        if (($result['status'] ?? 200) === 404) {
            return [];
        }
        if (
            isset($result['body']['Invoices']) &&
            is_array($result['body']['Invoices']) &&
            count($result['body']['Invoices']) > 0
        ) {
            return $result['body']['Invoices'][0];
        }
        return [];
    }

    public function onlineUrl(string $invoiceId): string
    {
        $result = parent::get('Invoices/' . $invoiceId . '/OnlineInvoice');

        return $result['body']['OnlineInvoices'][0]['OnlineInvoiceUrl'];
    }

    public function update(string $invoiceId, array $data): array
    {
        $result = parent::post('Invoices/' . $invoiceId, $data);

        return $result['body']['Invoices'][0];
    }

    public function store(array $data): array
    {
        $result = parent::post('Invoices', $data);

        return $result['body'];
    }

    public function attachments(string $invoiceId): array
    {
        $result = parent::get('Invoices/' . $invoiceId . '/Attachments');

        return $result['body']['Attachments'];
    }

    public function attachment(string $invoiceId, ?string $attachmentId = null, ?string $fileName = null): string
    {
        // Depending on the application, we may want to get it by the FileName instead of the AttachmentId
        $nameOrId = $attachmentId ? $attachmentId : $fileName;

        $result = parent::get('Invoices/' . $invoiceId . '/Attachments/' . $nameOrId);

        return $result['body'];
    }
}
