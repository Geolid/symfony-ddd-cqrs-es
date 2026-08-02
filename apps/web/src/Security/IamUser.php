<?php

declare(strict_types=1);

namespace Web\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class IamUser implements UserInterface
{
    public function __construct(private string $identityId)
    {
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->identityId;
    }
}
