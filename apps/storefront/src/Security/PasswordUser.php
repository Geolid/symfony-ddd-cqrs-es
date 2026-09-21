<?php

declare(strict_types=1);

namespace Storefront\Security;

use Iam\Identity\Application\IdentityModerationStatus;
use Iam\Identity\Application\IdentityVerificationStatus;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class PasswordUser implements UserInterface, EquatableInterface
{
    public function __construct(
        private string $identityId,
        private string $email,
        public string $fullName,
        public IdentityVerificationStatus $verificationStatus,
        public IdentityModerationStatus $moderationStatus,
        public \DateTimeImmutable $passwordChangedAt,
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function isEqualTo(UserInterface $user): bool
    {
        return $user instanceof self
            && $this->verificationStatus === $user->verificationStatus
            && $this->moderationStatus === $user->moderationStatus
            && $this->passwordChangedAt->format(\DateTimeInterface::ATOM) === $user->passwordChangedAt->format(\DateTimeInterface::ATOM);
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
