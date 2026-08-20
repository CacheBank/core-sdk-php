<?php

namespace CacheDigital\Core;

use CacheDigital\Core\Services\InvoiceService;

class CacheDigital
{
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $baseUrl;

    public function __construct(string $clientId, string $clientSecret, ?string $baseUrl = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->baseUrl = $baseUrl;
    }

    public static function make(string $clientId, string $clientSecret, ?string $baseUrl = null): self
    {
        return new static($clientId, $clientSecret, $baseUrl);
    }

    public function invoices(): InvoiceService
    {
        $service = new InvoiceService($this->baseUrl);
        return $service->authenticate($this->clientId, $this->clientSecret);
    }
}
