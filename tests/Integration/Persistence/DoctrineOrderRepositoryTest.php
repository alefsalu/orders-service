<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Domain\Model\Order;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\Quantity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineOrderRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testSaveAndRetrieveOrderWithItemsAndOptimisticLocking(): void
    {
        $container = self::getContainer();
        if (!$container->has(EntityManagerInterface::class)) {
            self::markTestSkipped('Database environment not active');
        }

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $orderId = OrderId::generate();
        $order = Order::create($orderId, CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->addItem('sku-1', 'Keyboard', Money::fromDecimal('150.00', Currency::BRL), Quantity::fromInt(1));

        $em->persist($order);
        $em->flush();
        $em->clear();

        /** @var Order|null $loadedOrder */
        $loadedOrder = $em->find(Order::class, $orderId->value());
        self::assertNotNull($loadedOrder);
        self::assertSame('150.00', $loadedOrder->totalAmount()->amount());
        self::assertSame(1, $loadedOrder->version());
        self::assertCount(1, $loadedOrder->items());
    }

    // Review Focus 4: Optimistic lock detection
    public function testOptimisticLockExceptionOnConcurrentUpdate(): void
    {
        $container = self::getContainer();
        if (!$container->has(EntityManagerInterface::class)) {
            self::markTestSkipped('Database environment not active');
        }

        /** @var EntityManagerInterface $em1 */
        $em1 = $container->get(EntityManagerInterface::class);

        $orderId = OrderId::generate();
        $order1 = Order::create($orderId, CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order1->addItem('sku-1', 'Keyboard', Money::fromDecimal('150.00', Currency::BRL), Quantity::fromInt(1));
        $em1->persist($order1);
        $em1->flush();

        /** @var Order|null $orderA */
        $orderA = $em1->find(Order::class, $orderId->value());
        self::assertNotNull($orderA);

        // Simulate concurrent process changing version in DB directly
        $em1->getConnection()->executeStatement('UPDATE orders SET version = version + 1 WHERE id = ?', [$orderId->value()]);

        $this->expectException(OptimisticLockException::class);
        $orderA->place();
        $em1->flush();
    }
}
