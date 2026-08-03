<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Security\AuthenticateApiTokenCredentialInterface;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Psr\Clock\ClockInterface;

final readonly class ApiTokenCredentialAuthenticationService implements AuthenticateApiTokenCredentialInterface
{
    public function __construct(
        private ApiTokenCredentialFinderInterface $apiTokenCredentialFinder,
        private ApiTokenCredentialRepositoryInterface $apiTokenCredentialRepository,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApiTokenCredentialNotFoundException
     */
    public function authenticate(string $identifier, string $plainSecret): ?string
    {
        $credentialResult = $this->apiTokenCredentialFinder->ofIdentifier($identifier);

        if (null === $credentialResult) {
            return null;
        }

        $credential = $this->apiTokenCredentialRepository->load(ApiTokenCredentialId::fromString($credentialResult->id));

        if (!$credential->verify($plainSecret, $this->hasher, $this->clock->now())) {
            return null;
        }

        return $credentialResult->identityId;
    }
}
