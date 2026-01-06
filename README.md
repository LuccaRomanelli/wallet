# Wallet API

API RESTful para transferências de dinheiro entre usuários, similar ao PIX brasileiro.

## Funcionalidades

- Criação de usuários comuns e lojas (merchants)
- Transferências de dinheiro entre usuários
- Validação de saldo em tempo real
- Autorização externa de transferências
- Notificações assíncronas para beneficiários

## Requisitos

- Docker

## Instalação

### Configuração de Usuário do Sail

Para evitar problemas de permissão com o Sail, certifique-se de que as variáveis `WWWGROUP` e `WWWUSER` estejam configuradas corretamente executando o comando abaixo:

```shell
echo "$(id -u):$(id -g)" # retorna <WWWUSER>:<WWWGROUP>
```

Copie o arquivo de exemplo e configure o ambiente:

```shell
cp .env.example .env
```

Depois atualize o arquivo `.env` conforme o exemplo abaixo:

```
WWWGROUP=<WWWGROUP> # resultado de id -g
WWWUSER=<WWWUSER> # resultado de id -u
```

### Instalar dependências

Após isso, execute o seguinte comando para instalar todas as dependências:

```shell
docker run -it \
    -u "$(id -u):$(id -g)" \
    -v ${PWD}/:/var/www/html \
    -w /var/www/html \
    composer:lts \
    composer install --ignore-platform-reqs --no-scripts
```

### Criar container

Quando terminar, execute o comando abaixo para criar o container Docker do projeto:

```shell
vendor/bin/sail up --detach --force-recreate laravel.test
vendor/bin/sail composer run post-autoload-dump
```

### Executar migrações

```shell
vendor/bin/sail artisan migrate
```

## Acessando a API

Após iniciar os containers, a API estará disponível em:

```
http://localhost
```

## Endpoints da API

| Método | Endpoint                  | Descrição                  |
|--------|---------------------------|----------------------------|
| POST   | `/api/users`              | Criar usuário comum        |
| POST   | `/api/stores`             | Criar loja (merchant)      |
| POST   | `/api/transfer`           | Realizar transferência     |
| GET    | `/api/users/{id}/balance` | Consultar saldo do usuário |

## Exemplos de Uso

### Criar Usuário

```bash
curl -X POST http://localhost/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@email.com",
    "password": "senha12345",
    "document": "12345678901",
    "start_money": 100.00
  }'
```

**Resposta:**
```json
{
  "id": 1,
  "name": "João Silva",
  "email": "joao@email.com"
}
```

### Criar Loja (Merchant)

```bash
curl -X POST http://localhost/api/stores \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Loja do Pedro",
    "email": "loja@email.com",
    "password": "senha12345",
    "document": "12345678000199",
    "start_money": 0.00
  }'
```

**Resposta:**
```json
{
  "id": 2,
  "name": "Loja do Pedro",
  "email": "loja@email.com"
}
```

### Realizar Transferência

```bash
curl -X POST http://localhost/api/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 50.00,
    "payer": 1,
    "payee": 2
  }'
```

**Resposta:**
```json
{
  "id": 1,
  "payer_id": 1,
  "payee_id": 2,
  "amount": "50.00",
  "status": "completed"
}
```

### Consultar Saldo do Usuário

```bash
curl -X GET http://localhost/api/users/1/balance
```

**Resposta:**
```json
{
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com",
    "document": "123.456.789-00",
    "user_type": "common",
    "balance": "50.00"
  }
}
```

## Regras de Negócio

- **Usuários comuns** podem enviar e receber transferências
- **Merchants (lojas)** só podem receber transferências, não podem enviar
- Todas as transferências passam por um serviço de autorização externo
- O saldo deve ser suficiente para realizar a transferência
- Não é permitido transferir para si mesmo
- Notificações são enviadas de forma assíncrona para o beneficiário

## Códigos de Erro

| Código | Descrição                                    |
|--------|----------------------------------------------|
| 201    | Sucesso                                      |
| 403    | Merchant tentando enviar / Self-transfer     |
| 404    | Usuário não encontrado                       |
| 422    | Saldo insuficiente / Validação falhou        |
| 503    | Serviço de autorização indisponível/negado   |

## Stack Tecnológica

- **Framework:** Laravel 12
- **PHP:** 8.4 com Octane/Swoole
- **Banco de Dados:** PostgreSQL 18
- **Cache/Filas:** Redis
- **Ambiente:** Docker com Laravel Sail
- **Testes:** Pest PHP

## Testes

```bash
vendor/bin/sail test
```

## Arquitetura

O projeto segue uma arquitetura em camadas:

- **Controllers:** Recebem requisições e delegam para services
- **Services:** Contêm a lógica de negócio
- **Repositories:** Abstraem o acesso aos dados
- **Value Objects:** Encapsulam valores (Money, Document)
- **Enums:** Definem tipos (UserType, TransactionStatus)
