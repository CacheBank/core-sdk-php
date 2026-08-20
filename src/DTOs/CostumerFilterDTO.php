<?php

namespace CacheDigital\Core\DTOs;

class CostumerFilterDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $tax_identifier = null,
        public ?string $phone = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id'       => $this->id,
            'name'     => $this->name,
            'email'    => $this->email,
            'tax_identifier' => $this->tax_identifier,
            'phone' => $this->phone,
        ], fn($value) => !is_null($value));
    }
}
