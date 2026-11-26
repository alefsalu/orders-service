# ADR 0002: Doctrine XML Mappings External to Domain

## Status
Accepted

## Context
Doctrine ORM supports mapping database schemas via PHP attributes (`#[ORM\Entity]`), YAML (deprecated), and XML. Placing PHP attributes inside domain entities couples the domain layer directly to `doctrine/orm`, violating clean architecture principles and architectural dependency gates.

## Decision
We configure Doctrine mappings exclusively through XML schema files located in `config/doctrine/`:
- `config/doctrine/Order.orm.xml`
- `config/doctrine/OrderLine.orm.xml`
- `config/doctrine/OutboxMessage.orm.xml`

Entities in `src/Domain/Model` contain zero ORM imports or attributes.

## Consequences
### Positive
- Strict domain purity: entities are plain PHP classes with standard constructors, private encapsulation, and domain methods.
- Deptrac layer validation passes with zero violations (`Domain` imports zero external packages).
- Database column names, indexes, and cascades are managed independently of business naming.

### Negative
- XML files are more verbose than inline attributes and require maintaining separate files when domain attributes change.
