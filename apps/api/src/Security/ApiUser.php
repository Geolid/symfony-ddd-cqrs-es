<?php

declare(strict_types=1);

namespace Api\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class ApiUser implements UserInterface
{
    public function __construct(
        public string $id,
        private string $identityId,
        private string $keyId,
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        \assert('' !== $this->keyId);

        return $this->keyId;
    }

    public function identityId(): string
    {
        return $this->identityId;
    }
}
