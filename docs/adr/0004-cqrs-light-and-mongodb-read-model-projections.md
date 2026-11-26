# ADR 0004: CQRS Light and MongoDB Read Model Projections

## Status
Accepted

## Context
E-commerce order history queries often require multi-dimensional auditing: tracking exact timelines of transitions, item pricing at time of purchase, and historical event details. Performing continuous complex historical queries and event timeline aggregation against the primary MySQL OLTP database can degrade write throughput and complicate relational schemas.

## Decision
We implement a lightweight CQRS (Command Query Responsibility Segregation) pattern:
- **Write Side (Commands)**: Executes against MySQL using the `Order` aggregate root and enforces all transactional invariants.
- **Read Side (Projections)**: As domain events occur, an `OrderHistoryProjector` updates a document in MongoDB (`order_history` collection), storing the order status, items, amounts, and an appended chronological timeline of all domain events.
- Audit history queries (`GET /api/v1/orders/{id}/history`) query MongoDB directly.

## Consequences
### Positive
- Offloads heavy historical and audit query traffic from the primary relational database.
- MongoDB schema-free documents provide flexibility for appending arbitrary event payload structures into the timeline.
- High-performance, single-document retrieval for customer order timeline screens.

### Negative
- Requires running and maintaining MongoDB in addition to MySQL.
- Projection updates are eventually consistent with respect to outbox relay execution.
