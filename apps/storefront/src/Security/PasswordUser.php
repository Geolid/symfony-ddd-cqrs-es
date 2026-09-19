<?php

declare(strict_types=1);

namespace Storefront\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class PasswordUser implements UserInterface
{
    public function __construct(
        private string $identityId,
        private string $email,
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
        \assert('' !== $this->email);

        return $this->email;
    }

    public function identityId(): string
    {
        return $this->identityId;
    }
}
