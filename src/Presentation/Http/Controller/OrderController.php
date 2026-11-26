<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use App\Application\Command\AddOrderItemCommand;
use App\Application\Command\CancelOrderCommand;
use App\Application\Command\ConfirmOrderPaymentCommand;
use App\Application\Command\CreateOrderCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\RemoveOrderItemCommand;
use App\Application\Command\ShipOrderCommand;
use App\Application\CommandHandler\OrderCommandHandler;
use App\Application\Query\GetOrderQuery;
use App\Application\Query\ListOrdersQuery;
use App\Application\QueryHandler\OrderQueryHandler;
use App\Domain\Exception\OrderNotFoundException;
use App\Infrastructure\Security\JwtUser;
use App\Presentation\Http\Request\AddOrderItemRequest;
use App\Presentation\Http\Request\CancelOrderRequest;
use App\Presentation\Http\Request\CreateOrderRequest;
use App\Presentation\Http\Request\PayConfirmRequest;
use App\Presentation\Http\Request\ShipOrderRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderCommandHandler $commandHandler,
        private readonly OrderQueryHandler $queryHandler,
    ) {
    }

    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] ?JwtUser $user): JsonResponse
    {
        $payload = (array) json_decode((string) $request->getContent(), true);
        $dto = CreateOrderRequest::fromArray($payload);

        $customerId = $dto->customerId ?? $user?->getUserIdentifier() ?? '00000000-0000-0000-0000-000000000001';
        $correlationId = (string) $request->attributes->get('correlationId', '');

        $orderId = $this->commandHandler->handleCreateOrder(new CreateOrderCommand($customerId, $correlationId));
        $order = $this->queryHandler->handleGetOrder(new GetOrderQuery($orderId));

        return new JsonResponse($order?->jsonSerialize(), Response::HTTP_CREATED);
    }

    #[Route('/{id}/items', name: 'api_orders_add_item', methods: ['POST'])]
    public function addItem(string $id, Request $request): JsonResponse
    {
        $payload = (array) json_decode((string) $request->getContent(), true);
        $dto = AddOrderItemRequest::fromArray($payload);
        $correlationId = (string) $request->attributes->get('correlationId', '');

        $this->commandHandler->handleAddOrderItem(new AddOrderItemCommand(
            orderId: $id,
            productId: $dto->productId,
            productName: $dto->productName,
            unitPriceAmount: $dto->unitPrice['amount'],
            unitPriceCurrency: $dto->unitPrice['currency'],
            quantity: $dto->quantity,
            correlationId: $correlationId,
        ));

        $order = $this->queryHandler->handleGetOrder(new GetOrderQuery($id));

        return new JsonResponse($order?->jsonSerialize(), Response::HTTP_OK);
    }

    #[Route('/{id}/items/{itemId}', name: 'api_orders_remove_item', methods: ['DELETE'])]
    public function removeItem(string $id, string $itemId, Request $request): JsonResponse
    {
        $correlationId = (string) $request->attributes->get('correlationId', '');

        $this->commandHandler->handleRemoveOrderItem(new RemoveOrderItemCommand($id, $itemId, $correlationId));
        $order = $this->queryHandler->handleGetOrder(new GetOrderQuery($id));

        return new JsonResponse($order?->jsonSerialize(), Response::HTTP_OK);
    }

    #[Route('/{id}/place', name: 'api_orders_place', methods: ['POST'])]
    public function place(string $id, Request $request): JsonResponse
    {
        $correlationId = (string) $request->attributes->get('correlationId', '');

        $this->commandHandler->handlePlaceOrder(new PlaceOrderCommand($id, $correlationId));
        $order = $this->queryHandler->handleGetOrder(new GetOrderQuery($id));

        return new JsonResponse($order?->jsonSerialize(), Response::HTTP_OK);
    }

    #[Route('/{id}/pay-confirm', name: 'api_orders_pay_confirm', methods: ['POST'])]
    public function payConfirm(string $id, Request $request): JsonResponse
    {
        $payload = (array) json_decode((string) $request->getContent(), true);
        $dto = PayConfirmRequest::fromArray($payload);
        $correlationId = (string) $request->attributes->get('correlationId', '');

        $this->commandHandler->handleConfirmPayment(new ConfirmOrderPaymentCommand(
            orderId: $id,
            paymentReference: $dto->paymentReference,
            amount: $dto->amount['amount'],
            currency: $dto->amount['currency'],
            correlationId: $correlationId,
        ));

        $order = $this->queryHandler->handleGetOrder(new GetOrderQuery($id));

        return new JsonResponse($order?->jsonSerialize(), Response::HTTP_OK);
    }

    #[Route('/{id}/ship', name: 'api_orders_ship', methods: ['POST'])]
    public function ship(string $id, Request $request): JsonResponse
    {
        $payload = (array) json_decode((string) $request->getContent(), true);
        $dto = ShipOrderRequest::fromArray($payload);
        $correlationId = (string) $request->attributes->get('correlationId', '');

        $this->commandHandler->handleShipOrder(new ShipOrderCommand($id, $dto->trackingNumber, $correlationId));
        $order = $this->queryHandler->handleGetOrder(new GetOrderQuery($id));

        return new JsonResponse($order?->jsonSerialize(), Response::HTTP_OK);
    }

    #[Route('/{id}/cancel', name: 'api_orders_cancel', methods: ['POST'])]
    public function cancel(string $id, Request $request): JsonResponse
    {
        $payload = (array) json_decode((string) $request->getContent(), true);
        $dto = CancelOrderRequest::fromArray($payload);
        $correlationId = (string) $request->attributes->get('correlationId', '');

        $this->commandHandler->handleCancelOrder(new CancelOrderCommand($id, $dto->reason, $correlationId));
        $order = $this->queryHandler->handleGetOrder(new GetOrderQuery($id));

        return new JsonResponse($order?->jsonSerialize(), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_orders_get', methods: ['GET'])]
    public function getOrder(string $id): JsonResponse
    {
        $order = $this->queryHandler->handleGetOrder(new GetOrderQuery($id));
        if ($order === null) {
            throw new OrderNotFoundException($id);
        }

        return new JsonResponse($order->jsonSerialize(), Response::HTTP_OK);
    }

    #[Route('', name: 'api_orders_list', methods: ['GET'])]
    public function listOrders(Request $request, #[CurrentUser] ?JwtUser $user): JsonResponse
    {
        $page = (int) $request->query->get('page', '0');
        $size = (int) $request->query->get('size', '20');
        $customerId = $request->query->has('customerId')
            ? (string) $request->query->get('customerId')
            : ($user?->getUserIdentifier());

        $result = $this->queryHandler->handleListOrders(new ListOrdersQuery($customerId, $page, $size));

        return new JsonResponse($result->jsonSerialize(), Response::HTTP_OK);
    }
}
