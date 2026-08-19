<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Identity\Application\Status\IdentityStatus;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class PasswordUser implements UserInterface
{
    /**
     * @param list<string> $grants
     */
    public function __construct(
        private string $identityId,
        private string $login,
        private array $grants = [],
        public IdentityStatus $identityStatus = IdentityStatus::ACTIVE,
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_USER', ...$this->grants];
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
