# Wallet API

API RESTful para transferências de dinheiro entre usuários, similar ao PIX brasileiro.

## Funcionalidades

- Criação de usuários comuns e lojas (merchants)
- Transferências de dinheiro entre usuários
- Validação de saldo em tempo real
- Autorização externa de transferências
- Notificações assíncronas para beneficiários

## Requisitos

- PHP 8.4+
- Composer
- Docker (recomendado) ou:
  - PostgreSQL 18
  - Redis

## Instalação

### Com Docker (Recomendado)

```bash
# Clone o repositório
git clone <url-do-repositorio>
cd wallet

# Instale as dependências e configure o ambiente
composer run setup

# Inicie os containers
./vendor/bin/sail up -d

# Execute as migrações
./vendor/bin/sail artisan migrate
```

### Sem Docker

```bash
# Clone o repositório
git clone <url-do-repositorio>
cd wallet

# Instale as dependências
composer install

# Configure o ambiente
cp .env.example .env
php artisan key:generate

# Configure as variáveis de banco de dados no .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=wallet
# DB_USERNAME=seu_usuario
# DB_PASSWORD=sua_senha

# Execute as migrações
php artisan migrate

# Inicie o servidor
php artisan serve
```

## Endpoints da API

| Método | Endpoint         | Descrição               |
|--------|------------------|-------------------------|
| POST   | `/api/users`     | Criar usuário comum     |
| POST   | `/api/stores`    | Criar loja (merchant)   |
| POST   | `/api/transfer`  | Realizar transferência  |

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
# Com Docker
./vendor/bin/sail test

# Sem Docker
./vendor/bin/pest
```

## Arquitetura

O projeto segue uma arquitetura em camadas:

- **Controllers:** Recebem requisições e delegam para services
- **Services:** Contêm a lógica de negócio
- **Repositories:** Abstraem o acesso aos dados
- **Value Objects:** Encapsulam valores (Money, Document)
- **Enums:** Definem tipos (UserType, TransactionStatus)
