<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential;

use Iam\Authentication\Application\Credential\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;

final readonly class PasswordCredentialVerifier implements PasswordCredentialVerifierInterface
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private PasswordHasherInterface $hasher,
    ) {
    }

    /**
     * @throws PasswordCredentialResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $plainPassword): bool
    {
        $credential = $this->passwordCredentialFinder->ofIdentity($identityId);

        if (!$credential->identityAuthenticatable) {
            throw IdentityNotAuthenticatableException::forIdentity($identityId);
        }

        return $this->hasher->verify($credential->passwordHash, $plainPassword);
    }
}
