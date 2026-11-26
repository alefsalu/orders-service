<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CorrelationIdListener
{
    private const HEADER_NAME = 'X-Correlation-Id';

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $correlationId = $request->headers->get(self::HEADER_NAME);

        if ($correlationId === null || trim($correlationId) === '') {
            $data = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
            $correlationId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        $request->attributes->set('correlationId', $correlationId);
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: -100)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $correlationId = (string) $request->attributes->get('correlationId', '');

        if ($correlationId !== '') {
            $event->getResponse()->headers->set(self::HEADER_NAME, $correlationId);
        }
    }
}
