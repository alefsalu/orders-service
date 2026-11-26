<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly JwtTokenValidator $validator)
    {
    }

    public function supports(Request $request): ?bool
    {
        $header = $request->headers->get('Authorization');

        return $header !== null && str_starts_with($header, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = (string) $request->headers->get('Authorization');
        $token = substr($header, 7);

        $user = $this->validator->validate($token);

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), static fn () => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $problem = [
            'type' => 'https://api.portfolio.dev/errors/unauthorized',
            'title' => 'Unauthorized',
            'status' => Response::HTTP_UNAUTHORIZED,
            'detail' => $exception->getMessage(),
            'instance' => $request->getPathInfo(),
            'code' => 'UNAUTHORIZED',
        ];

        return new JsonResponse($problem, Response::HTTP_UNAUTHORIZED, [
            'Content-Type' => 'application/problem+json',
        ]);
    }
}
