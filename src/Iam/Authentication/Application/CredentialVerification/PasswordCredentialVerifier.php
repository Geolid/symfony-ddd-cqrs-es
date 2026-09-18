<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;

final readonly class PasswordCredentialVerifier implements PasswordCredentialVerifierInterface
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private IdentityFinderInterface $identityFinder,
        private PasswordHasherInterface $hasher,
    ) {
    }

    /**
     * @throws PasswordCredentialResultNotFoundException
     * @throws IdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $plainPassword): bool
    {
        $credential = $this->passwordCredentialFinder->ofIdentity($identityId);

        $identity = $this->identityFinder->ofId($identityId);

        if (!$identity->isAuthenticatable()) {
            throw IdentityNotAuthenticatableException::forIdentity($identityId);
        }

        return $this->hasher->verify($credential->passwordHash, $plainPassword);
    }
}
