<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;

final readonly class ApiKeyCredentialVerifier implements ApiKeyCredentialVerifierInterface
{
    public function __construct(
        private ApiKeyCredentialFinderInterface $apiKeyCredentialFinder,
        private ApiKeyHasherInterface $hasher,
    ) {
    }

    public function verify(string $keyId, #[\SensitiveParameter] string $secret): bool
    {
        $credential = $this->apiKeyCredentialFinder->ofKeyId($keyId);

        if ($credential->revoked) {
            throw ApiKeyCredentialRevokedException::forKeyId($keyId);
        }

        return $this->hasher->verify($credential->secretHash, $secret);
    }
}
