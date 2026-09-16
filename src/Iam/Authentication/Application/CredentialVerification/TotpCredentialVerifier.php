<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;

final readonly class TotpCredentialVerifier implements TotpCredentialVerifierInterface
{
    public function __construct(
        private TotpCredentialFinderInterface $totpCredentialFinder,
        private TotpCipherInterface $cipher,
        private TotpVerifierInterface $verifier,
    ) {
    }

    /**
     * @throws TotpCredentialResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool
    {
        $credential = $this->totpCredentialFinder->ofIdentity($identityId);

        if (!$credential->identityAuthenticatable) {
            throw IdentityNotAuthenticatableException::forIdentity($identityId);
        }

        return $this->verifier->verify($this->cipher->decrypt($credential->encryptedSecret), $code);
    }
}
