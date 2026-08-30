<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential\ApiKey;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;

final readonly class ApiKeyCredentialVerifier implements ApiKeyCredentialVerifierInterface
{
    public function __construct(
        private ApiKeyCredentialFinderInterface $apiKeyCredentialFinder,
        private ApiKeyHasherInterface $hasher,
    ) {
    }

    /**
     * @throws ApiKeyCredentialResultNotFoundException
     * @throws ApiKeyCredentialRevokedException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $keyId, #[\SensitiveParameter] string $secret): bool
    {
        $credential = $this->apiKeyCredentialFinder->ofKeyId($keyId);

        if ($credential->revoked) {
            throw ApiKeyCredentialRevokedException::forKeyId($keyId);
        }

        if (!$credential->identityAuthenticatable) {
            throw IdentityNotAuthenticatableException::forIdentity($credential->identityId);
        }

        return $this->hasher->verify($credential->secretHash, $secret);
    }
}
