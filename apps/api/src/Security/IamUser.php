<?php

declare(strict_types=1);

namespace Api\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class IamUser implements UserInterface
{
    /**
     * @param list<string> $grants
     */
    public function __construct(
        private string $identityId,
        private array $grants = [],
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_USER', ...$this->grants];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->identityId) {
            throw new \LogicException('IamUser cannot carry an empty identity id.');
        }

        return $this->identityId;
    }
}
