# Segurança

## Como relatar uma vulnerabilidade

Envie um e-mail para **suporte@cachedigital.com.br** com:

- a descrição do problema e o impacto;
- os passos para reproduzir;
- a versão do SDK.

Não abra issue pública nem divulgue o problema antes de recebermos e corrigirmos. Respondemos em até 3 dias úteis.

## Boas práticas para quem usa o SDK

- Guarde `client_id` e `client_secret` só no servidor (variáveis de ambiente ou cofre de segredos).
- Nunca envie o `client_secret` para navegador, aplicativo ou repositório.
- Se uma credencial vazar, gere uma nova no painel (**Integração API > Chaves de acesso**) e desative a antiga.
- Use sempre HTTPS (o endereço padrão já é HTTPS).
