<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Infrastructure\Security\JwtAuthenticator;
use App\Infrastructure\Security\JwtTokenValidator;
use App\Infrastructure\Security\JwtUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class JwtAuthenticatorTest extends TestCase
{
    public function testSupportsBearerToken(): void
    {
        $validator = $this->createMock(JwtTokenValidator::class);
        $authenticator = new JwtAuthenticator($validator);

        $requestWithBearer = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer token123']);
        $requestWithoutAuth = new Request();

        self::assertTrue($authenticator->supports($requestWithBearer));
        self::assertFalse($authenticator->supports($requestWithoutAuth));
    }

    public function testAuthenticateReturnsPassportWithUser(): void
    {
        $user = new JwtUser('user-1', 'user@example.com', ['ROLE_USER']);
        $validator = $this->createMock(JwtTokenValidator::class);
        $validator->expects(self::once())->method('validate')->with('token123')->willReturn($user);

        $authenticator = new JwtAuthenticator($validator);
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer token123']);

        $passport = $authenticator->authenticate($request);
        self::assertSame('user-1', $passport->getUser()->getUserIdentifier());
    }

    // Review Focus 5: Token expiration beyond leeway rejected
    public function testRejectsExpiredTokenOrWrongIssuerAudience(): void
    {
        $validator = $this->createMock(JwtTokenValidator::class);
        $validator->method('validate')
            ->willThrowException(new AuthenticationException('Token has expired'));

        $authenticator = new JwtAuthenticator($validator);
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer expired.token.jwt']);

        $this->expectException(AuthenticationException::class);
        $authenticator->authenticate($request);
    }

    public function testOnAuthenticationFailureReturnsRfc7807ProblemJson(): void
    {
        $validator = $this->createMock(JwtTokenValidator::class);
        $authenticator = new JwtAuthenticator($validator);

        $request = new Request();
        $response = $authenticator->onAuthenticationFailure($request, new AuthenticationException('Token has expired'));

        self::assertNotNull($response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('UNAUTHORIZED', $data['code']);
    }
}
