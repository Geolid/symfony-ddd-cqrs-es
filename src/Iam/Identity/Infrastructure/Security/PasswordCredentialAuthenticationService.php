<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Port\AuthenticatePasswordCredentialInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\IdentityStatus;
use Iam\Identity\Domain\PasswordCredentialId;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;

final readonly class PasswordCredentialAuthenticationService implements AuthenticatePasswordCredentialInterface
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private PasswordCredentialRepositoryInterface $passwordCredentialRepository,
        private IdentityRepositoryInterface $identityRepository,
        private SecretHasherInterface $hasher,
    ) {
    }

    /**
     * @throws PasswordCredentialNotFoundException
     * @throws IdentityNotFoundException
     */
    public function authenticate(string $login, string $plainPassword): ?string
    {
        $credentialResult = null;

        foreach ($this->passwordCredentialFinder as $candidate) {
            if ($candidate->login === $login) {
                $credentialResult = $candidate;

                break;
            }
        }

        if (null === $credentialResult) {
            return null;
        }

        $credential = $this->passwordCredentialRepository->load(PasswordCredentialId::fromString($credentialResult->id));

        if (!$credential->verify($plainPassword, $this->hasher)) {
            return null;
        }

        $identity = $this->identityRepository->load(IdentityId::fromString($credentialResult->identityId));

        if (IdentityStatus::ACTIVE !== $identity->status()) {
            return null;
        }

        return $credentialResult->identityId;
    }
}
