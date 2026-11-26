<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class JwtUser implements UserInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private readonly string $userId,
        private readonly string $email,
        private readonly array $roles = ['ROLE_USER'],
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $normalized = [];
        foreach ($this->roles as $role) {
            $r = strtoupper(trim($role));
            if (!str_starts_with($r, 'ROLE_')) {
                $r = 'ROLE_' . $r;
            }
            $normalized[] = $r;
        }

        if (!in_array('ROLE_USER', $normalized, true)) {
            $normalized[] = 'ROLE_USER';
        }

        return array_values(array_unique($normalized));
    }

    public function eraseCredentials(): void
    {
    }
}
