<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Security\ApiTokenCredentialVerifierInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;

final readonly class ApiTokenCredentialVerifier implements ApiTokenCredentialVerifierInterface
{
    public function __construct(
        private ApiTokenCredentialFinderInterface $apiTokenCredentialFinder,
        private SecretHasherInterface $hasher,
    ) {
    }

    /**
     * @throws ApiTokenCredentialResultNotFoundException
     */
    public function verify(string $identifier, #[\SensitiveParameter] string $plainSecret): bool
    {
        $credential = $this->apiTokenCredentialFinder->ofIdentifier($identifier);

        return $this->hasher->verify($credential->hash, $plainSecret);
    }
}
