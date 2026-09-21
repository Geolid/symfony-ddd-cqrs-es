<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\Command\ConsumeBackupCode\ConsumeBackupCode;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class TotpCredentialVerifier implements TotpCredentialVerifierInterface
{
    public function __construct(
        private TotpCredentialFinderInterface $totpCredentialFinder,
        private TotpCipherInterface $cipher,
        private TotpVerifierInterface $verifier,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool
    {
        $credential = $this->totpCredentialFinder->activeOfIdentityOrNull($identityId);

        if (null === $credential) {
            return false;
        }

        if ($this->verifier->verify($this->cipher->decrypt($credential->encryptedSecret), $code)) {
            return true;
        }

        try {
            $this->commandBus->dispatch(new ConsumeBackupCode($credential->id, $code));

            return true;
        } catch (ApplicationExceptionInterface|\DomainException) {
            return false;
        }
    }
}
