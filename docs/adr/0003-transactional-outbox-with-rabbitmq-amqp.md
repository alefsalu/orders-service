# ADR 0003: Transactional Outbox Pattern with RabbitMQ AMQP

## Status
Accepted

## Context
When an order lifecycle state changes (e.g., placed, paid, shipped), downstream services like `notification-service` must be notified via message broker. Publishing directly to RabbitMQ within the HTTP request lifecycle risks dual-write anomalies (e.g., database commit succeeds but message publishing fails, or publishing succeeds but database transaction rolls back).

## Decision
We implement the Transactional Outbox Pattern:
1. When mutations occur, the aggregate records domain events.
2. In the application command handler, aggregate changes and the corresponding `OutboxMessage` entries are persisted to MySQL within the same ACID transaction.
3. An `OutboxRelayService` (invoked periodically via console worker `orders:outbox:relay`) polls pending messages (`published_at IS NULL`), publishes them to RabbitMQ topic exchange `orders.events` using publisher confirms, and updates `published_at = NOW()`.
4. If RabbitMQ is unavailable, failed attempts are recorded and messages remain preserved in the outbox table for retry.

## Consequences
### Positive
- Guaranteed at-least-once delivery of domain events without distributed 2PC transactions.
- Zero lost messages during network partitions or broker downtime.
- Preserves transactional atomicity between order state changes and event emission.

### Negative
- Consumers must be idempotent as at-least-once delivery may produce duplicate messages during recovery.
- Slight latency introduced between aggregate save and event relay.
