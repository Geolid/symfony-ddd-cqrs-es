<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Port\AuthenticateApiTokenCredentialInterface;
use Iam\Identity\Domain\ApiTokenCredentialId;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\IdentityStatus;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Psr\Clock\ClockInterface;

final readonly class ApiTokenCredentialAuthenticationService implements AuthenticateApiTokenCredentialInterface
{
    public function __construct(
        private ApiTokenCredentialFinderInterface $apiTokenCredentialFinder,
        private ApiTokenCredentialRepositoryInterface $apiTokenCredentialRepository,
        private IdentityRepositoryInterface $identityRepository,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    public function authenticate(string $identifier, string $plainSecret): ?string
    {
        $credentialResult = null;

        foreach ($this->apiTokenCredentialFinder as $candidate) {
            if ($candidate->identifier === $identifier) {
                $credentialResult = $candidate;

                break;
            }
        }

        if (null === $credentialResult) {
            return null;
        }

        $credential = $this->apiTokenCredentialRepository->load(ApiTokenCredentialId::fromString($credentialResult->id));

        if (!$credential->verify($plainSecret, $this->hasher, $this->clock->now())) {
            return null;
        }

        $identity = $this->identityRepository->load(IdentityId::fromString($credentialResult->identityId));

        if (IdentityStatus::ACTIVE !== $identity->status()) {
            return null;
        }

        return $credentialResult->identityId;
    }
}
