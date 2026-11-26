# ADR 0001: Hexagonal Architecture with Pure Domain Model

## Status
Accepted

## Context
The Orders Service manages critical e-commerce business operations, order lifecycle states, and item invariant rules. Embedding framework annotations or infrastructure concepts directly into domain entities introduces tight coupling, hinders business logic testability, and leaks database details into business code.

## Decision
We adopt Hexagonal (Ports & Adapters) Clean Architecture:
- `src/Domain`: 100% pure PHP 8.3 code without framework attributes, Doctrine annotations, or third-party dependencies. Contains Aggregate Roots (`Order`), Entities (`OrderLine`), Value Objects (`Money`, `OrderId`, `Quantity`), Domain Events, and Repository Interfaces.
- `src/Application`: Application CQRS commands, queries, DTOs, and orchestration handlers.
- `src/Infrastructure`: Adapters implementing domain repository interfaces via Doctrine ORM, MongoDB client, AMQP publisher, and JWT authenticator.
- `src/Presentation`: HTTP Controllers and Request DTOs.
- Architectural boundary enforcement is automated via **Deptrac** and tested in CI.

## Consequences
### Positive
- Domain models are completely isolated and can be tested with instantaneous unit tests without booting Symfony or running a database.
- Long-term maintainability: upgrading framework or ORM versions does not impact core business logic.
- Clear mental separation of business rules and infrastructure mechanics.

### Negative
- Requires mapping between domain objects and presentation DTOs.
- Requires external mapping configuration (XML) rather than convenient inline PHP attributes.
