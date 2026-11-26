<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController extends AbstractController
{
    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(): Response
    {
        $metrics = [];
        $metrics[] = '# HELP php_orders_total Total count of processed orders';
        $metrics[] = '# TYPE php_orders_total counter';
        $metrics[] = 'php_orders_total{status="draft"} 0';
        $metrics[] = 'php_orders_total{status="placed"} 0';
        $metrics[] = 'php_orders_total{status="paid"} 0';
        $metrics[] = 'php_orders_total{status="shipped"} 0';
        $metrics[] = 'php_orders_total{status="cancelled"} 0';
        $metrics[] = '';
        $metrics[] = '# HELP http_requests_total Total number of HTTP requests';
        $metrics[] = '# TYPE http_requests_total counter';
        $metrics[] = 'http_requests_total{service="orders-service"} 1';
        $metrics[] = '';
        $metrics[] = '# HELP memory_usage_bytes Current PHP memory usage';
        $metrics[] = '# TYPE memory_usage_bytes gauge';
        $metrics[] = sprintf('memory_usage_bytes{service="orders-service"} %d', memory_get_usage(true));
        $metrics[] = '';

        return new Response(implode("\n", $metrics) . "\n", Response::HTTP_OK, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
