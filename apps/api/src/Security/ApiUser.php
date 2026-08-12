<?php

declare(strict_types=1);

namespace Api\Security;

use Iam\Identity\Application\Enum\IdentityStatus;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class ApiUser implements UserInterface
{
    /**
     * @param list<string> $grants
     */
    public function __construct(
        public string $id,
        private string $identityId,
        private string $identifier,
        private array $grants,
        public bool $revoked,
        public \DateTimeImmutable $expiresAt,
        public IdentityStatus $identityStatus,
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
        \assert('' !== $this->identifier);

        return $this->identifier;
    }

    public function identityId(): string
    {
        return $this->identityId;
    }
}
