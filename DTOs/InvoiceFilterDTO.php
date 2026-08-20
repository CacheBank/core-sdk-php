<?php

namespace CacheDigital\Core\DTOs;

class InvoiceFilterDTO
{
    public function __construct(
        public ?string $status = null,
        public ?int $filterdate = null,
        public ?array $filterdatevalue = null, // ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'status'          => $this->status,
            'filterdate'      => $this->filterdate,
            'filterdatevalue' => $this->filterdatevalue,
        ], fn($value) => !is_null($value));
    }
}
