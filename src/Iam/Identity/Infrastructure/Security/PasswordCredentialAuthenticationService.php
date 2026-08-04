<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Security\AuthenticatePasswordCredentialInterface;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;

final readonly class PasswordCredentialAuthenticationService implements AuthenticatePasswordCredentialInterface
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private PasswordCredentialRepositoryInterface $passwordCredentialRepository,
        private SecretHasherInterface $hasher,
    ) {
    }

    /**
     * @throws PasswordCredentialNotFoundException
     */
    public function authenticate(string $login, string $plainPassword): ?string
    {
        $credentialResult = $this->passwordCredentialFinder->ofLogin($login);

        if (null === $credentialResult) {
            return null;
        }

        $credential = $this->passwordCredentialRepository->load(PasswordCredentialId::fromString($credentialResult->id));

        if (!$credential->verify($plainPassword, $this->hasher)) {
            return null;
        }

        return $credentialResult->identityId;
    }
}
