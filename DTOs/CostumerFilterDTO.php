<?php

namespace CacheDigital\Core\DTOs;

class CostumerFilterDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $document = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id'       => $this->id,
            'name'     => $this->name,
            'email'    => $this->email,
            'document' => $this->document,
        ], fn($value) => !is_null($value));
    }
}
