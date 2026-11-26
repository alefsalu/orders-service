# Orders Service

[![CI/CD Pipeline](https://github.com/alef-salu/orders-service/actions/workflows/ci.yml/badge.svg)](https://github.com/alef-salu/orders-service/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.1-black.svg)](https://symfony.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

High-performance Orders microservice built in **PHP 8.3** and **Symfony 7** running on **FrankenPHP**. The service implements **Domain-Driven Design (DDD)** with a 100% pure domain model, external **Doctrine ORM 3 XML mappings**, **CQRS** with Symfony Messenger, asynchronous **MongoDB read projections**, and a **Transactional Outbox relay** to RabbitMQ.

---

## Architecture Overview

```mermaid
flowchart TD
    Client[Web & Mobile Clients] -->|HTTPS REST + RS256 JWT| OrderController[OrderController]
    OrderController -->|Dispatch Commands| CommandBus[OrderCommandHandler]
    OrderController -->|Dispatch Queries| QueryBus[OrderQueryHandler]

    subgraph Hexagonal Domain
        CommandBus --> OrderAggregate[Order Aggregate Root]
        OrderAggregate --> DomainEvents[Domain Events]
    end

    subgraph Persistence & Outbox
        OrderAggregate -->|Doctrine XML Mapping| MySQL[(MySQL 8.0: orders, order_lines)]
        DomainEvents -->|Same Transaction| Outbox[(MySQL 8.0: outbox)]
    end

    subgraph Messaging & Relay
        Outbox -->|Polling / CDC Relay| OutboxRelay[OutboxRelayService]
        OutboxRelay -->|Publisher Confirms| RabbitMQ[RabbitMQ: orders.events Topic Exchange]
    end

    subgraph Read Model Projection
        DomainEvents -.-> Projector[OrderHistoryProjector]
        Projector --> MongoDB[(MongoDB 7.0: order_history)]
        QueryBus --> MongoDB
    end
```

### Key Architectural Characteristics

1. **Pure Domain Model**: Zero framework dependencies or annotations in `src/Domain`. Persistence details are isolated in `config/doctrine/*.orm.xml`. Architecture boundaries are strictly enforced via **Deptrac**.
2. **Transactional Outbox Pattern**: Aggregate mutations and domain events are persisted in a single ACID transaction in MySQL, eliminating dual-write anomalies. An outbox relay publishes events to RabbitMQ with publisher confirms.
3. **Optimistic Concurrency**: Orders use entity versioning (`version`) to guarantee safe concurrent modifications and reject conflicting writes with HTTP 409 Conflict.
4. **Audit Read Projection**: Read history and event timelines are projected to MongoDB (`order_history` collection) for high-speed audit queries without adding query load to MySQL.
5. **RS256 JWT Authentication**: Acts as an OAuth2 / JWT Resource Server validating cryptographically signed tokens issued by `wallet-api` with RSA public key (`keys/public.pem`).
6. **Cross-Cutting Standards**: RFC 7807 problem details (`application/problem+json`), correlation ID propagation (`X-Correlation-Id`), and OWASP security headers on all responses.

---

## API Endpoints

Base URL: `/api/v1`

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `POST` | `/api/v1/orders` | Create draft order | Yes (`ROLE_USER`) |
| `POST` | `/api/v1/orders/{id}/items` | Add product item to draft | Yes (`ROLE_USER`) |
| `DELETE` | `/api/v1/orders/{id}/items/{itemId}` | Remove item from draft | Yes (`ROLE_USER`) |
| `POST` | `/api/v1/orders/{id}/place` | Place order (validates items > 0) | Yes (`ROLE_USER`) |
| `POST` | `/api/v1/orders/{id}/pay-confirm` | Confirm payment with matching amount | Yes (`ROLE_USER`) |
| `POST` | `/api/v1/orders/{id}/ship` | Mark paid order as shipped | Yes (`ROLE_USER`) |
| `POST` | `/api/v1/orders/{id}/cancel` | Cancel draft or placed order | Yes (`ROLE_USER`) |
| `GET` | `/api/v1/orders/{id}` | Get order details | Yes (`ROLE_USER`) |
| `GET` | `/api/v1/orders?page=0&size=20` | List paginated orders | Yes (`ROLE_USER`) |
| `GET` | `/api/v1/orders/{id}/history` | Get order event timeline audit | Yes (`ROLE_USER`) |
| `GET` | `/health/live` | Liveness health probe | No (Public) |
| `GET` | `/health/ready` | Readiness probe (MySQL, MongoDB, RabbitMQ) | No (Public) |
| `GET` | `/metrics` | Prometheus metrics exposition | No (Public) |

---

## Getting Started

### Prerequisites

- Docker 24+ and Docker Compose v2+
- Make

### Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/alef-salu/orders-service.git
cd orders-service

# 2. Generate RSA keys for dev token signing
make keys

# 3. Start services (FrankenPHP, MySQL 8, MongoDB 7, RabbitMQ 3.13)
make up

# 4. Run database migrations
docker compose run --rm app bin/console doctrine:migrations:migrate --no-interaction

# 5. Check health
curl http://localhost:8081/health/live
```

---

## Development & Testing Commands

| Command | Description |
|---|---|
| `make up` | Start full environment in background |
| `make down` | Stop all containers |
| `make test` | Run all unit, architecture, and integration tests |
| `make test-unit` | Run fast unit and domain tests |
| `make test-integration`| Run integration tests with testcontainers/ephemeral compose |
| `make lint` | Run PHPStan (level max), PHP-CS-Fixer dry-run, and Deptrac |
| `make format` | Fix coding standard violations automatically |
| `make token` | Generate a signed RS256 dev token for local manual testing |
| `make guard` | Ensure no sensitive files or private keys are tracked in git |
| `make ci` | Full local pipeline check (`guard` + `lint` + `test`) |

---

## Environment Variables

| Variable | Default Value | Description |
|---|---|---|
| `APP_ENV` | `dev` | Symfony environment (`dev`, `prod`, `test`) |
| `DATABASE_URL` | `mysql://orders_user:orders_pass@mysql:3306/orders_db` | MySQL connection string |
| `MONGODB_URI` | `mongodb://mongodb:27017` | MongoDB connection URI |
| `MONGODB_DATABASE`| `orders_history` | MongoDB projection database name |
| `RABBITMQ_HOST` | `rabbitmq` | RabbitMQ broker hostname |
| `RABBITMQ_PORT` | `5672` | RabbitMQ AMQP port |
| `RABBITMQ_EXCHANGE`| `orders.events` | AMQP Topic exchange name |
| `JWT_PUBLIC_KEY_PATH` | `/app/keys/public.pem` | RSA public key path for signature verification |
| `JWT_ISSUER` | `wallet-api` | Expected JWT issuer claim (`iss`) |
| `JWT_AUDIENCE` | `portfolio` | Expected JWT audience claim (`aud`) |

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
