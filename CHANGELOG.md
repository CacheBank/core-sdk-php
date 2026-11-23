# Changelog

## Próxima versão

### Alterações
- `storefront()->order()` passa a chamar `POST /api/external/v1/storefront/order`, que respeita o limite de emissão de cobranças da conta. Acima do limite, a resposta é `422` com o erro em `order`. A rota antiga (`/api/v2/storefront/order`) continua existindo na Cachê, sem limite.

## 1.1.0

### Novidades
- `charges()`: cobranças Pix, Bolepix e carnê, consulta por id ou referência, lista, mudança de status, cancelamento de carnê e notificações.
- `costumers()->balance()`: saldo do cliente.
- `storefront()->order()`: compra pela vitrine (devolve a resposta, inclusive erros de validação).
- `request()`: chamada a qualquer endpoint da API v2 com as credenciais da conta.
- Erros padronizados:
  - `ApiException`, com `status()`, `errors()`, `isValidationError()` e `isRetryable()`;
  - `ConnectionException`;
  - base comum `CacheDigitalException`.
- Nova tentativa automática nas consultas (`GET`) em falha de rede, `5xx` e `429`. Criações nunca são repetidas.
- Opções `timeout`, `retries` e `retry_delay_ms`.
- Módulo `Integration`:
  - `Signature` (HMAC-SHA256 v1);
  - `RetryPolicy` (até 72 h);
  - `SignedClient`.
- Testes (PHPUnit) e documentação para clientes.

### Correções
- Sem `CACHE_DIGITAL_URL`, o SDK quebrava (`getenv()` devolve `false`, e o endereço padrão nunca era usado). Agora o padrão é `https://api.cachebank.com.br`.
- A configuração do Laravel (`cachedigital.url_digital_account`) passa a ser usada.

### Compatibilidade
- Mantidos: `CacheDigital::make()`, `invoices()->list()` (devolve o mesmo array) e o construtor de `InvoiceService`.
- Erros continuam estendendo `Exception`.
- Removido do `composer.json` do pacote o repositório interno (`repositories`), desnecessário para quem instala.

## 1.0.0
- Primeira versão: listagem de faturas (`invoices()->list()`).
