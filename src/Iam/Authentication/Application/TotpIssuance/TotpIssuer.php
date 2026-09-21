<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpIssuance;

use Iam\Authentication\Application\Command\IssueTotpCredential\IssueTotpCredential;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class TotpIssuer implements TotpIssuerInterface
{
    public function __construct(
        private TotpVerifierInterface $verifier,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws InvalidTotpCodeException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function issueFor(string $identityId, #[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code): void
    {
        if (!$this->verifier->verify($secret, $code)) {
            throw InvalidTotpCodeException::forIdentity($identityId);
        }

        $this->commandBus->dispatch(new IssueTotpCredential(Uuid::uuid7()->toString(), $identityId, $secret));
    }
}
