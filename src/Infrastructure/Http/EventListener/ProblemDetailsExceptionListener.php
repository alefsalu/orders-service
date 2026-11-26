<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Exception\EmptyOrderPlacementException;
use App\Domain\Exception\InvalidArgumentException;
use App\Domain\Exception\InvalidStateTransitionException;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Exception\OrderNotModifiableException;
use App\Domain\Exception\PaymentMismatchException;
use App\Infrastructure\Http\ProblemDetails;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class ProblemDetailsExceptionListener
{
    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $title = 'Internal Server Error';
        $code = 'INTERNAL_ERROR';
        $detail = $exception->getMessage();
        $type = 'https://api.portfolio.dev/errors/internal-error';

        if ($exception instanceof OrderNotFoundException) {
            $status = Response::HTTP_NOT_FOUND;
            $title = 'Not Found';
            $code = 'ORDER_NOT_FOUND';
            $type = 'https://api.portfolio.dev/errors/not-found';
        } elseif ($exception instanceof InvalidStateTransitionException) {
            $status = Response::HTTP_CONFLICT;
            $title = 'Invalid State Transition';
            $code = 'INVALID_STATE_TRANSITION';
            $type = 'https://api.portfolio.dev/errors/conflict';
        } elseif ($exception instanceof OrderNotModifiableException) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $title = 'Order Not Modifiable';
            $code = 'ORDER_NOT_MODIFIABLE';
            $type = 'https://api.portfolio.dev/errors/unprocessable';
        } elseif ($exception instanceof EmptyOrderPlacementException) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $title = 'Empty Order';
            $code = 'EMPTY_ORDER';
            $type = 'https://api.portfolio.dev/errors/unprocessable';
        } elseif ($exception instanceof PaymentMismatchException) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $title = 'Payment Mismatch';
            $code = 'PAYMENT_MISMATCH';
            $type = 'https://api.portfolio.dev/errors/unprocessable';
        } elseif ($exception instanceof OptimisticLockException) {
            $status = Response::HTTP_CONFLICT;
            $title = 'Concurrent Modification';
            $code = 'CONCURRENT_MODIFICATION';
            $type = 'https://api.portfolio.dev/errors/conflict';
        } elseif ($exception instanceof InvalidArgumentException) {
            $status = Response::HTTP_BAD_REQUEST;
            $title = 'Bad Request';
            $code = 'VALIDATION_ERROR';
            $type = 'https://api.portfolio.dev/errors/bad-request';
        } elseif ($exception instanceof AuthenticationException) {
            $status = Response::HTTP_UNAUTHORIZED;
            $title = 'Unauthorized';
            $code = 'UNAUTHORIZED';
            $type = 'https://api.portfolio.dev/errors/unauthorized';
        } elseif ($exception instanceof AccessDeniedException) {
            $status = Response::HTTP_FORBIDDEN;
            $title = 'Forbidden';
            $code = 'FORBIDDEN';
            $type = 'https://api.portfolio.dev/errors/forbidden';
        } elseif ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $title = Response::$statusTexts[$status] ?? 'HTTP Error';
            $code = match ($status) {
                404 => 'NOT_FOUND',
                400 => 'BAD_REQUEST',
                401 => 'UNAUTHORIZED',
                403 => 'FORBIDDEN',
                405 => 'METHOD_NOT_ALLOWED',
                409 => 'CONFLICT',
                422 => 'UNPROCESSABLE_ENTITY',
                default => 'HTTP_' . $status,
            };
            $type = 'https://api.portfolio.dev/errors/' . strtolower(str_replace('_', '-', $code));
        }

        $problem = new ProblemDetails(
            type: $type,
            title: $title,
            status: $status,
            detail: $detail,
            instance: $path,
            code: $code,
        );

        $response = new JsonResponse($problem->jsonSerialize(), $status, [
            'Content-Type' => 'application/problem+json',
        ]);

        $correlationId = (string) $request->attributes->get('correlationId', '');
        if ($correlationId !== '') {
            $response->headers->set('X-Correlation-Id', $correlationId);
        }

        $event->setResponse($response);
    }
}
