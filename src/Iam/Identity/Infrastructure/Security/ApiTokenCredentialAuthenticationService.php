<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\ApiTokenCredentialAuthenticationFailedException;
use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Security\ApiTokenCredentialAuthenticatorInterface;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Psr\Clock\ClockInterface;

final readonly class ApiTokenCredentialAuthenticationService implements ApiTokenCredentialAuthenticatorInterface
{
    public function __construct(
        private ApiTokenCredentialFinderInterface $apiTokenCredentialFinder,
        private ApiTokenCredentialRepositoryInterface $apiTokenCredentialRepository,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApiTokenCredentialAuthenticationFailedException
     * @throws ApiTokenCredentialNotFoundException
     */
    public function authenticate(string $identifier, string $plainSecret): string
    {
        try {
            $credentialResult = $this->apiTokenCredentialFinder->ofIdentifier($identifier);
        } catch (ApiTokenCredentialResultNotFoundException) {
            throw ApiTokenCredentialAuthenticationFailedException::forIdentifier($identifier);
        }

        $credential = $this->apiTokenCredentialRepository->load(ApiTokenCredentialId::fromString($credentialResult->id));

        if (!$credential->verify($plainSecret, $this->hasher, $this->clock->now())) {
            throw ApiTokenCredentialAuthenticationFailedException::forIdentifier($identifier);
        }

        return $credentialResult->identityId;
    }
}
