<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\PasswordCredentialAuthenticationFailedException;
use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Security\PasswordCredentialAuthenticatorInterface;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;

final readonly class PasswordCredentialAuthenticationService implements PasswordCredentialAuthenticatorInterface
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private PasswordCredentialRepositoryInterface $passwordCredentialRepository,
        private SecretHasherInterface $hasher,
    ) {
    }

    /**
     * @throws PasswordCredentialAuthenticationFailedException
     * @throws PasswordCredentialNotFoundException
     */
    public function authenticate(string $login, string $plainPassword): string
    {
        try {
            $credentialResult = $this->passwordCredentialFinder->ofLogin($login);
        } catch (PasswordCredentialResultNotFoundException) {
            throw PasswordCredentialAuthenticationFailedException::forLogin($login);
        }

        $credential = $this->passwordCredentialRepository->load(PasswordCredentialId::fromString($credentialResult->id));

        if (!$credential->verify($plainPassword, $this->hasher)) {
            throw PasswordCredentialAuthenticationFailedException::forLogin($login);
        }

        return $credentialResult->identityId;
    }
}
