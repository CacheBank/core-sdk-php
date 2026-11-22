<?php

namespace CacheDigital\Core\Exceptions;

use CacheDigital\Core\Http\ApiResponse;

/**
 * A API respondeu com erro (4xx/5xx). Traz o status e o corpo, para mostrar a mensagem da API ao usuário.
 *
 *   try { ... } catch (ApiException $e) { $e->status(); $e->errors(); $e->getMessage(); }
 */
class ApiException extends CacheDigitalException
{
    public function __construct(private ApiResponse $response, ?string $message = null)
    {
        parent::__construct($message ?? $response->errorMessage() ?? "Erro {$response->status()} na API Cache Digital.", $response->status());
    }

    public function response(): ApiResponse
    {
        return $this->response;
    }

    public function status(): int
    {
        return $this->response->status();
    }

    /** Erros de validação no formato da API ({ campo: [mensagens] }). */
    public function errors(): array
    {
        return $this->response->errors();
    }

    public function isValidationError(): bool
    {
        return $this->response->status() === 422;
    }

    /** Vale tentar de novo (erro do servidor, excesso de requisições, timeout). */
    public function isRetryable(): bool
    {
        return $this->response->status() >= 500 || in_array($this->response->status(), [408, 425, 429], true);
    }
}
