# Cachê Digital · SDK PHP

SDK oficial da [Cachê Digital](https://cachedigital.com.br) para PHP. Use a API da **sua conta** para criar e acompanhar cobranças **Pix** e **Bolepix** (boleto com QR Code Pix), carnês, consultar clientes e faturas, direto do seu sistema.

- PHP 8.1+ · Guzzle 7
- Funciona em qualquer projeto PHP e se integra sozinho ao Laravel
- Erros claros (status e mensagens da API), nova tentativa automática nas consultas

## Instalação

```bash
composer require cachedigital/core
```

## Credenciais

No painel da Cachê, acesse **Integração API > Chaves de acesso** e copie o `client_id` e o `client_secret` da sua conta.

> Guarde as credenciais só no servidor (variáveis de ambiente). **Nunca** coloque o `client_secret` em aplicativo, site ou repositório público.

```dotenv
CACHE_DIGITAL_URL=https://api.cachebank.com.br   # opcional (este é o padrão)
CACHE_DIGITAL_CLIENT_ID=...
CACHE_DIGITAL_CLIENT_SECRET=...
```

## Uso rápido

```php
use CacheDigital\Core\CacheDigital;

$cache = CacheDigital::make(getenv('CACHE_DIGITAL_CLIENT_ID'), getenv('CACHE_DIGITAL_CLIENT_SECRET'));

// Cobrança Pix (confirmação na hora)
$cobranca = $cache->charges()->pix([
    // campos conforme a documentação da API v2 da Cachê
    'reference' => 'PEDIDO-123',
    // ...
]);

// Consultar pela sua referência (evita duplicar ao tentar de novo)
$cobranca = $cache->charges()->findByReference('PEDIDO-123');
```

### Cobranças (`charges()`)

| Método | Descrição |
| --- | --- |
| `pix(array $dados)` | Cobrança Pix (QR Code dinâmico) |
| `boleto(array $dados)` | Bolepix: boleto e QR Code Pix na mesma cobrança |
| `carne(array $dados)` | Carnê (várias parcelas) |
| `find(string $id)` | Consulta pelo id da Cachê |
| `findByReference(string $ref)` | Consulta pela referência do seu sistema |
| `list(array $filtros)` | Lista cobranças |
| `changeStatus(string $status, array $dados)` | Altera a situação (ex.: cancelar) |
| `cancelCarne(array $dados)` | Cancela as parcelas em aberto de um carnê |
| `notifications(array $filtros)` | Notificações (webhooks) enviadas para uma cobrança |

> **Prefira o Pix.** Ele é confirmado na hora e o seu cliente paga em segundos. O Bolepix também traz o QR Code Pix.

### Faturas e clientes

```php
use CacheDigital\Core\DTOs\InvoiceListQueryDTO;
use CacheDigital\Core\DTOs\CostumerFilterDTO;
use CacheDigital\Core\DTOs\InvoiceFilterDTO;

$faturas = $cache->invoices()->list(new InvoiceListQueryDTO(
    limit: 20,
    costumer: new CostumerFilterDTO(tax_identifier: '12345678909'),
));

$saldo = $cache->costumers()->balance('ID_DO_CLIENTE');
```

### Outros endpoints

Para um endpoint da API v2 que ainda não tem método próprio:

```php
$resposta = $cache->request('GET', '/api/v2/...', ['query' => ['campo' => 'valor']]);
$resposta->body();
```

## Erros

```php
use CacheDigital\Core\Exceptions\ApiException;
use CacheDigital\Core\Exceptions\ConnectionException;

try {
    $cache->charges()->pix($dados);
} catch (ApiException $e) {
    $e->status();             // 422, 401...
    $e->getMessage();         // mensagem da API, pronta para mostrar
    $e->errors();             // ['campo' => ['mensagem']] nas validações
    $e->isValidationError();  // true para 422
} catch (ConnectionException $e) {
    // a API não respondeu: tente de novo mais tarde
}
```

Todas as exceções estendem `CacheDigital\Core\Exceptions\CacheDigitalException`, que estende `Exception`.

**Novas tentativas.** Consultas (`GET`) são repetidas sozinhas em falha de rede, `5xx` e `429` (2 vezes, por padrão). Criações e mudanças de status **nunca** são repetidas pelo SDK, para não duplicar cobranças. Antes de tentar de novo, confira com `findByReference()`.

## Configuração

```php
$cache = CacheDigital::make($clientId, $clientSecret, 'https://api.cachebank.com.br', [
    'timeout' => 15,          // segundos (padrão 10)
    'retries' => 2,           // novas tentativas nas consultas
    'retry_delay_ms' => 200,  // espera entre tentativas (cresce a cada tentativa)
]);
```

O endereço da API segue esta ordem: o valor informado → `config('cachedigital.url_digital_account')` (Laravel) → `CACHE_DIGITAL_URL` → `https://api.cachebank.com.br`.

### Laravel

O service provider é registrado automaticamente. Para publicar a configuração:

```bash
php artisan vendor:publish --tag=cachedigital-config
```

## Integrações avançadas

Para sistemas que trocam eventos assinados com a Cachê, o SDK traz as mesmas ferramentas usadas pela plataforma:

- `CacheDigital\Core\Integration\Signature`: assinatura **HMAC-SHA256** (`X-Timestamp` + `X-Signature: v1=…`), com verificação, tolerância de horário e troca de segredo sem parar;
- `CacheDigital\Core\Integration\RetryPolicy`: quando tentar de novo (10 s → … → 1 h, até 72 h);
- `CacheDigital\Core\Integration\SignedClient`: chamadas assinadas.

```php
use CacheDigital\Core\Integration\Signature;

$valido = Signature::verify($corpo, $headers['X-Timestamp'], $headers['X-Signature'], [$segredo]);
```

O algoritmo é simples de verificar em qualquer linguagem: `hmac_sha256("{timestamp}.{corpo}", segredo)`.

## Testes

```bash
composer install
composer test
```

## Segurança

Encontrou uma vulnerabilidade? Veja [SECURITY.md](SECURITY.md). **Não** abra uma issue pública.

## Licença

MIT. Veja [LICENSE](LICENSE).
