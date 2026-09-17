<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;

final readonly class TotpCredentialVerifier implements TotpCredentialVerifierInterface
{
    public function __construct(
        private TotpCredentialFinderInterface $totpCredentialFinder,
        private IdentityFinderInterface $identityFinder,
        private TotpCipherInterface $cipher,
        private TotpVerifierInterface $verifier,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool
    {
        $credential = $this->totpCredentialFinder->confirmedOfIdentityOrNull($identityId);

        if (null === $credential) {
            return false;
        }

        if (!$this->identityFinder->ofId($identityId)->status->isActive()) {
            throw IdentityNotAuthenticatableException::forIdentity($identityId);
        }

        return $this->verifier->verify($this->cipher->decrypt($credential->encryptedSecret), $code);
    }
}
