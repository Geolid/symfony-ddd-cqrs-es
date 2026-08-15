<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Security\PasswordCredentialVerifierInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;

final readonly class PasswordCredentialVerifier implements PasswordCredentialVerifierInterface
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private SecretHasherInterface $hasher,
    ) {
    }

    /**
     * @throws PasswordCredentialResultNotFoundException
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $plainSecret): bool
    {
        $credential = $this->passwordCredentialFinder->ofIdentityId($identityId);

        return $this->hasher->verify($credential->hash, $plainSecret);
    }
}
