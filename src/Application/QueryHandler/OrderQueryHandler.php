<?php

declare(strict_types=1);

namespace App\Application\QueryHandler;

use App\Application\DTO\OrderDTO;
use App\Application\DTO\OrderLineDTO;
use App\Application\DTO\PageResult;
use App\Application\Query\GetOrderHistoryQuery;
use App\Application\Query\GetOrderQuery;
use App\Application\Query\ListOrdersQuery;
use App\Domain\Model\Order;
use App\Domain\Model\OrderLine;
use App\Domain\Repository\OrderHistoryRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\OrderId;

class OrderQueryHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ?OrderHistoryRepositoryInterface $historyRepository = null,
    ) {
    }

    public function handleGetOrder(GetOrderQuery $query): ?OrderDTO
    {
        $order = $this->orderRepository->findById(OrderId::fromString($query->orderId));
        if ($order === null) {
            return null;
        }

        return $this->toDTO($order);
    }

    /**
     * @return PageResult<OrderDTO>
     */
    public function handleListOrders(ListOrdersQuery $query): PageResult
    {
        $page = max(0, $query->page);
        $size = min(100, max(1, $query->size));

        if ($query->customerId !== null && $query->customerId !== '') {
            $customerId = CustomerId::fromString($query->customerId);
            $orders = $this->orderRepository->findByCustomerId($customerId, $page, $size);
            $totalElements = $this->orderRepository->countByCustomerId($customerId);
        } else {
            $orders = $this->orderRepository->findAllPaginated($page, $size);
            $totalElements = $this->orderRepository->countAll();
        }

        $totalPages = (int) ceil($totalElements / $size);
        $dtos = array_map(fn (Order $order): OrderDTO => $this->toDTO($order), $orders);

        return new PageResult(
            content: $dtos,
            page: $page,
            size: $size,
            totalElements: $totalElements,
            totalPages: $totalPages,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function handleGetOrderHistory(GetOrderHistoryQuery $query): ?array
    {
        if ($this->historyRepository === null) {
            return null;
        }

        $projection = $this->historyRepository->findByOrderId($query->orderId);
        if ($projection === null) {
            return null;
        }

        return $projection->toArray();
    }

    private function toDTO(Order $order): OrderDTO
    {
        $items = array_map(
            static fn (OrderLine $line): OrderLineDTO => new OrderLineDTO(
                id: $line->id()->value(),
                productId: $line->productId(),
                productName: $line->productName(),
                unitPrice: $line->unitPrice()->jsonSerialize(),
                quantity: $line->quantity()->value(),
                totalPrice: $line->totalPrice()->jsonSerialize(),
            ),
            $order->items()
        );

        return new OrderDTO(
            id: $order->id()->value(),
            customerId: $order->customerId()->value(),
            status: $order->status()->value,
            items: $items,
            totalAmount: $order->totalAmount()->jsonSerialize(),
            version: $order->version(),
            createdAt: $order->createdAt()->format(\DateTimeInterface::RFC3339),
            updatedAt: $order->updatedAt()->format(\DateTimeInterface::RFC3339),
        );
    }
}
