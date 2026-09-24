<?php

declare(strict_types=1);

namespace Storefront\Security;

use Iam\Identity\Application\IdentityModerationStatus;
use Iam\Identity\Application\IdentityVerificationStatus;
use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Storefront\Security\Provider\TotpTwoFactorProvider;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class PasswordUser implements UserInterface, EquatableInterface, TrustedDeviceInterface, PreferredProviderInterface
{
    public function __construct(
        private string $identityId,
        private string $email,
        public string $fullName,
        public IdentityVerificationStatus $verificationStatus,
        public IdentityModerationStatus $moderationStatus,
        public \DateTimeImmutable $passwordChangedAt,
        private ?\DateTimeImmutable $deviceTrustRevokedAt,
    ) {
    }

    public function getTrustedTokenVersion(): int
    {
        return $this->deviceTrustRevokedAt?->getTimestamp() ?? 0;
    }

    public function getPreferredTwoFactorProvider(): string
    {
        return TotpTwoFactorProvider::ALIAS;
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
