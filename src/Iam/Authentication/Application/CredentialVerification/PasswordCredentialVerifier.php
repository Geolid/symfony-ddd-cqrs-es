<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;

final readonly class PasswordCredentialVerifier implements PasswordCredentialVerifierInterface
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private PasswordHasherInterface $hasher,
    ) {
    }

    public function verify(string $identityId, #[\SensitiveParameter] string $plainPassword): bool
    {
        $credential = $this->passwordCredentialFinder->ofIdentityOrNull($identityId);

        if (null === $credential) {
            return false;
        }

        return $this->hasher->verify($credential->passwordHash, $plainPassword);
    }
}
