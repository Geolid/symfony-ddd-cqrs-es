<?php

declare(strict_types=1);

namespace Web\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class PasswordUser implements UserInterface
{
    public function __construct(
        private string $identityId,
        private string $login,
        public bool $authenticatable,
        public string $passwordChangedAt,
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        \assert('' !== $this->login);

        return $this->login;
    }

    public function identityId(): string
    {
        return $this->identityId;
    }
}
