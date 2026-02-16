<?php

declare(strict_types=1);

namespace Almani\Xero\Resources;

use Almani\Xero\Enums\FilterOptions;
use Almani\Xero\Xero;
use InvalidArgumentException;

class CreditNotes extends Xero
{
    protected array $queryString = [];

    /**
     * Apply a filter to the CreditNotes query
     *
     * @param string $key
     * @param mixed  $value
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

    /**
     * Fetch credit notes
     *
     * @return array
     */
    public function get(): array
    {
        $queryString = $this->formatQueryStrings($this->queryString);

        $result = parent::get('CreditNotes?' . $queryString);

        return $result['body']['CreditNotes'];
    }

    /**
     * Find a specific credit note
     *
     * @param string $creditNoteId
     * @return array
     */
    public function find(string $creditNoteId): array
    {
        $result = parent::get('CreditNotes/' . $creditNoteId);

        return $result['body']['CreditNotes'][0];
    }

    /**
     * Update a credit note
     *
     * @param string $creditNoteId
     * @param array  $data
     * @return array
     */
    public function update(string $creditNoteId, array $data): array
    {
        $result = $this->post('CreditNotes/' . $creditNoteId, $data);

        return $result['body']['CreditNotes'][0];
    }

    /**
     * Store (create) a new credit note
     *
     * @param array $data
     * @return array
     */
    public function store(array $data): array
    {
        $result = $this->post('CreditNotes', $data);

        return $result['body']['CreditNotes'][0];
    }
}
