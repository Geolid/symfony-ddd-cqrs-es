<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Security\ApiTokenCredentialIssuerInterface;
use Iam\Identity\Application\Security\IssuedApiKey;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ApiTokenCredentialIssuingService implements ApiTokenCredentialIssuerInterface
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function issue(string $identityId, \DateTimeImmutable $expiresAt): IssuedApiKey
    {
        $identifier = 'key_'.bin2hex(random_bytes(8));
        $secret = bin2hex(random_bytes(32));

        $this->commandBus->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identityId,
            identifier: $identifier,
            secret: $secret,
            expiresAt: $expiresAt->format(\DateTimeInterface::ATOM),
        ));

        return new IssuedApiKey($identifier, $secret);
    }
}
