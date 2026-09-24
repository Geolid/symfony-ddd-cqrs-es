<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

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

    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool
    {
        $credential = $this->totpCredentialFinder->activeOfIdentityOrNull($identityId);

        if (null === $credential) {
            return false;
        }

        return $this->verifier->verify($this->cipher->decrypt($credential->encryptedSecret), $code);
    }
}
