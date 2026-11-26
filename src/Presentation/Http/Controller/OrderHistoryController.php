<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use App\Application\Query\GetOrderHistoryQuery;
use App\Application\QueryHandler\OrderQueryHandler;
use App\Domain\Exception\OrderNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderHistoryController extends AbstractController
{
    public function __construct(private readonly OrderQueryHandler $queryHandler)
    {
    }

    #[Route('/api/v1/orders/{id}/history', name: 'api_orders_history', methods: ['GET'])]
    public function getHistory(string $id): JsonResponse
    {
        $history = $this->queryHandler->handleGetOrderHistory(new GetOrderHistoryQuery($id));
        if ($history === null) {
            throw new OrderNotFoundException($id);
        }

        return new JsonResponse($history, Response::HTTP_OK);
    }
}
