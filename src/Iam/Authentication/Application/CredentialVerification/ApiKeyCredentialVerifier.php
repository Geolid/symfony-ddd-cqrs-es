<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;

final readonly class ApiKeyCredentialVerifier implements ApiKeyCredentialVerifierInterface
{
    public function __construct(
        private ApiKeyCredentialFinderInterface $apiKeyCredentialFinder,
        private IdentityFinderInterface $identityFinder,
        private ApiKeyHasherInterface $hasher,
    ) {
    }

    /**
     * @throws ApiKeyCredentialResultNotFoundException
     * @throws ApiKeyCredentialRevokedException
     * @throws IdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $keyId, #[\SensitiveParameter] string $secret): bool
    {
        $credential = $this->apiKeyCredentialFinder->ofKeyId($keyId);

        if ($credential->revoked) {
            throw ApiKeyCredentialRevokedException::forKeyId($keyId);
        }

        $identity = $this->identityFinder->ofId($credential->identityId);

        if (!$identity->status->isActive()) {
            throw IdentityNotAuthenticatableException::forIdentity($credential->identityId);
        }

        return $this->hasher->verify($credential->secretHash, $secret);
    }
}
