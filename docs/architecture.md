# Architecture Documentation

## 1. C4 Context Diagram

The following diagram illustrates how `orders-service` fits into the overall system landscape, interacting with external users, the identity provider (`wallet-api`), and downstream asynchronous event consumers.

```mermaid
C4Context
    title System Context diagram for Orders Service

    Person(customer, "Customer", "A user placing, managing, and tracking e-commerce orders")
    System_Ext(walletApi, "Wallet API", "Issues RS256 JWT tokens with public key distribution")
    System(ordersService, "Orders Service", "Manages order lifecycle, invariants, outbox publishing, and audit read projections")
    System_Ext(notificationService, "Notification Service", "Consumes domain events to dispatch transactional notifications")

    Rel(customer, ordersService, "Places orders, adds items, requests status", "HTTPS / JSON REST API")
    Rel(ordersService, walletApi, "Validates RS256 JWT tokens using published RSA public key", "Public Key Verification")
    Rel(ordersService, notificationService, "Emits order lifecycle events (placed, paid, shipped)", "RabbitMQ AMQP: orders.events")
```

---

## 2. C4 Container Diagram

The internal container diagram of `orders-service` demonstrates the separation between application processes, relational persistence, event messaging, and document projections.

```mermaid
C4Container
    title Container diagram for Orders Service

    Person(client, "Web / Mobile Client", "Interacts via REST API")

    Container_Boundary(ordersBoundary, "Orders Service")
        Container(app, "FrankenPHP / Symfony 7 Application", "PHP 8.3", "Exposes REST endpoints, validates JWTs, processes commands and queries, manages domain invariants")
        Container(relay, "Transactional Outbox Relay Worker", "CLI / Symfony Console", "Periodically scans outbox table and reliably delivers domain events with publisher confirms")
        ContainerDb(mysql, "MySQL 8.0 Database", "Relational Database", "Stores Order aggregate roots, OrderLine entities, and transactional outbox table")
        ContainerDb(mongo, "MongoDB 7.0 Database", "Document Database", "Maintains order_history audit projections for fast multi-dimensional timeline queries")
    end

    Container_Ext(rabbitmq, "RabbitMQ Broker", "AMQP 0-9-1", "Topic exchange 'orders.events' routing domain events to subscribers")

    Rel(client, app, "Sends API requests", "HTTPS/JSON, RS256 Bearer")
    Rel(app, mysql, "Reads and writes aggregates & outbox", "Doctrine ORM 3 (XML)")
    Rel(app, mongo, "Queries order history and projects events", "MongoDB PHP Driver")
    Rel(relay, mysql, "Fetches unpublished events & marks published", "Doctrine DBAL")
    Rel(relay, rabbitmq, "Publishes events with publisher confirms", "AMQP 0-9-1")
```

---

## 3. Data Flow & Transactional Outbox Pattern

```mermaid
sequenceDiagram
    autonumber
    actor User as Client
    participant Controller as OrderController
    participant Handler as OrderCommandHandler
    participant Aggregate as Order Aggregate Root
    participant MySQL as MySQL (orders & outbox)
    participant Relay as OutboxRelayService
    participant RabbitMQ as RabbitMQ (orders.events)
    participant MongoDB as MongoDB (order_history)

    User->>Controller: POST /api/v1/orders/{id}/place
    Controller->>Handler: dispatch PlaceOrderCommand
    Handler->>Aggregate: order->place()
    Note over Aggregate: Enforces status transition DRAFT -> PLACED<br/>and items count > 0
    Aggregate-->>Handler: recorded events: [OrderPlacedDomainEvent]
    Handler->>MySQL: BEGIN TRANSACTION
    Handler->>MySQL: UPDATE orders SET status='PLACED', version=version+1
    Handler->>MySQL: INSERT INTO outbox (event_type, payload, published_at=NULL)
    Handler->>MySQL: COMMIT
    Handler-->>Controller: Success
    Controller-->>User: 200 OK

    loop Background Outbox Relay
        Relay->>MySQL: SELECT * FROM outbox WHERE published_at IS NULL LIMIT 100
        Relay->>RabbitMQ: BasicPublish(exchange='orders.events', key='orders.order.placed.v1')
        RabbitMQ-->>Relay: BasicAck
        Relay->>MySQL: UPDATE outbox SET published_at = NOW() WHERE id = :id
        Relay->>MongoDB: Upsert order_history timeline
    end
```
