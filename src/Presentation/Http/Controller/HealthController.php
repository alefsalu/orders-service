<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use App\Infrastructure\Monitoring\HealthCheckService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    public function __construct(private readonly HealthCheckService $healthCheckService)
    {
    }

    #[Route('/health/live', name: 'health_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return new JsonResponse(['status' => 'UP'], Response::HTTP_OK);
    }

    #[Route('/health/ready', name: 'health_ready', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        $result = $this->healthCheckService->checkReadiness();
        $statusCode = $result['status'] === 'UP' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse($result, $statusCode);
    }
}
