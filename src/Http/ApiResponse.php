<?php

namespace CacheDigital\Core\Http;

/** Resposta da API: status HTTP e corpo já decodificado. */
class ApiResponse
{
    public function __construct(private int $status, private array $body, private string $raw = '')
    {
    }

    public function status(): int
    {
        return $this->status;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /** Corpo completo (array). */
    public function body(): array
    {
        return $this->body;
    }

    /** Valor do corpo por caminho com pontos (ex.: "data.invoice_id"). */
    public function get(string $path, $default = null)
    {
        $value = $this->body;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function raw(): string
    {
        return $this->raw;
    }

    /** Erros no formato { campo: [mensagens] }, como a API devolve nas validações. */
    public function errors(): array
    {
        $errors = $this->body['errors'] ?? $this->body['error'] ?? [];

        return is_array($errors) ? $errors : ['error' => [(string) $errors]];
    }

    /** Primeira mensagem de erro legível da resposta, se houver. */
    public function errorMessage(): ?string
    {
        foreach (['message', 'error'] as $key) {
            if (isset($this->body[$key]) && is_string($this->body[$key]) && $this->body[$key] !== '') {
                return $this->body[$key];
            }
        }
        foreach ($this->errors() as $messages) {
            $first = is_array($messages) ? reset($messages) : $messages;
            if (is_string($first) && $first !== '') {
                return $first;
            }
        }

        return null;
    }
}
