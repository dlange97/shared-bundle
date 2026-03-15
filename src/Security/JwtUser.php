<?php

declare(strict_types=1);

namespace MyDashboard\Shared\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;

final class JwtUser implements JWTUserInterface
{
    /**
     * @param list<string> $roles
     * @param list<string> $permissions
     */
    public function __construct(
        private readonly string $userId,
        private readonly string $email,
        private readonly ?string $firstName,
        private readonly ?string $lastName,
        private readonly array $roles,
        private readonly array $permissions,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function createFromPayload($username, array $payload): self
    {
        return new self(
            userId: (string) ($payload['id'] ?? ''),
            email: $username,
            firstName: $payload['firstName'] ?? null,
            lastName: $payload['lastName'] ?? null,
            roles: $payload['roles'] ?? ['ROLE_USER'],
            permissions: $payload['permissions'] ?? [],
        );
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /** @return list<string> */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
