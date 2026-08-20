<?php

namespace CacheDigital\Core\DTOs;

class InvoiceListQueryDTO
{
    public function __construct(
        public ?int $limit = 15,
        public ?string $orderBy = 'due_date',
        public ?string $orderByType = 'asc',
        public ?CostumerFilterDTO $costumer = null,
        public ?InvoiceFilterDTO $invoice = null,
    ) {}

    /**
     * Converte o DTO para o formato de array esperado pela API
     */
    public function toArray(): array
    {
        $payload = array_filter([
            'limit'       => $this->limit,
            'orderBy'     => $this->orderBy,
            'orderByType' => $this->orderByType,
        ], fn($value) => !is_null($value));

        $queryString = [];

        if ($this->costumer) {
            $queryString['costumer'] = $this->costumer->toArray();
        }

        if ($this->invoice) {
            $queryString['invoice'] = $this->invoice->toArray();
        }


        if (!empty($queryString)) {
            $payload['queryString'] = $queryString;
        }

        return $payload;
    }
}
