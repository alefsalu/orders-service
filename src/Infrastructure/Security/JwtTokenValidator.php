<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtTokenValidator
{
    public function __construct(
        private readonly string $publicKeyPath = '/app/keys/public.pem',
        private readonly string $expectedIssuer = 'wallet-api',
        private readonly string $expectedAudience = 'portfolio',
        private readonly int $leewaySeconds = 30,
    ) {
    }

    public function validate(string $jwt): JwtUser
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new AuthenticationException('Malformed JWT token.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $headerJson = $this->base64UrlDecode($headerB64);
        $payloadJson = $this->base64UrlDecode($payloadB64);
        $signature = $this->base64UrlDecode($signatureB64);

        /** @var array<string, mixed>|null $header */
        $header = json_decode($headerJson, true);
        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) {
            throw new AuthenticationException('Invalid JWT JSON structure.');
        }

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new AuthenticationException(sprintf('Unsupported JWT algorithm: "%s", expected "RS256".', (string) ($header['alg'] ?? 'none')));
        }

        // Verify cryptographic signature if public key is present
        if (file_exists($this->publicKeyPath)) {
            $publicKeyContent = file_get_contents($this->publicKeyPath);
            if ($publicKeyContent === false) {
                throw new AuthenticationException('Cannot read public key for JWT validation.');
            }

            $pubKeyResource = openssl_pkey_get_public($publicKeyContent);
            if ($pubKeyResource === false) {
                throw new AuthenticationException('Invalid public key format.');
            }

            $dataToVerify = $headerB64 . '.' . $payloadB64;
            $verifyResult = openssl_verify($dataToVerify, $signature, $pubKeyResource, OPENSSL_ALGO_SHA256);

            if ($verifyResult !== 1) {
                throw new AuthenticationException('Invalid JWT signature.');
            }
        }

        // Validate Issuer
        $iss = $payload['iss'] ?? null;
        if ($iss !== $this->expectedIssuer) {
            throw new AuthenticationException(sprintf('Invalid JWT issuer: "%s", expected "%s".', (string) $iss, $this->expectedIssuer));
        }

        // Validate Audience
        $aud = $payload['aud'] ?? null;
        if ($aud !== $this->expectedAudience) {
            throw new AuthenticationException(sprintf('Invalid JWT audience: "%s", expected "%s".', (string) $aud, $this->expectedAudience));
        }

        // Validate Expiration with leeway
        $now = time();
        $exp = $payload['exp'] ?? null;
        if (!is_int($exp) && !is_numeric($exp)) {
            throw new AuthenticationException('Missing or invalid "exp" claim.');
        }

        if ($now > ((int) $exp + $this->leewaySeconds)) {
            throw new AuthenticationException('Token has expired.');
        }

        // Validate Subject
        $sub = $payload['sub'] ?? null;
        if (!is_string($sub) || trim($sub) === '') {
            throw new AuthenticationException('Missing or invalid "sub" claim.');
        }

        $email = (string) ($payload['email'] ?? 'user@example.com');
        /** @var list<string> $roles */
        $roles = isset($payload['roles']) && is_array($payload['roles']) ? array_values($payload['roles']) : ['ROLE_USER'];

        return new JwtUser($sub, $email, $roles);
    }

    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder > 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($input, '-_', '+/'), true);
        if ($decoded === false) {
            throw new AuthenticationException('Base64URL decode failed.');
        }

        return $decoded;
    }
}
