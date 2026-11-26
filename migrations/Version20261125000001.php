<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create orders, order_lines, and transactional outbox tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE orders (
            id CHAR(36) NOT NULL,
            customer_id CHAR(36) NOT NULL,
            status VARCHAR(32) NOT NULL,
            total_amount NUMERIC(12, 2) NOT NULL,
            total_currency VARCHAR(3) NOT NULL,
            version INT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_orders_customer_id (customer_id),
            INDEX idx_orders_status (status),
            INDEX idx_orders_created_at (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE order_lines (
            id CHAR(36) NOT NULL,
            order_id CHAR(36) NOT NULL,
            product_id VARCHAR(64) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            unit_price NUMERIC(12, 2) NOT NULL,
            unit_currency VARCHAR(3) NOT NULL,
            quantity INT NOT NULL,
            total_price NUMERIC(12, 2) NOT NULL,
            total_currency VARCHAR(3) NOT NULL,
            INDEX idx_order_lines_order_id (order_id),
            INDEX idx_order_lines_product_id (product_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_order_lines_orders FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE outbox (
            id CHAR(36) NOT NULL,
            event_type VARCHAR(128) NOT NULL,
            aggregate_type VARCHAR(64) NOT NULL,
            aggregate_id CHAR(36) NOT NULL,
            correlation_id CHAR(36) NOT NULL,
            payload JSON NOT NULL,
            occurred_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            attempts INT NOT NULL DEFAULT 0,
            INDEX idx_outbox_status_occurred (published_at, occurred_at),
            INDEX idx_outbox_event_type (event_type),
            INDEX idx_outbox_correlation_id (correlation_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS outbox');
        $this->addSql('DROP TABLE IF EXISTS order_lines');
        $this->addSql('DROP TABLE IF EXISTS orders');
    }
}
