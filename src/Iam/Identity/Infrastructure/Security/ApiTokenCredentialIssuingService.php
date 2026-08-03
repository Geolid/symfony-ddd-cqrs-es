<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Security\IssueApiTokenCredentialInterface;
use Iam\Identity\Application\Security\IssuedApiKey;
use Iam\Identity\Domain\ApiTokenCredentialId;
use Shared\Application\Command\CommandBusInterface;

final readonly class ApiTokenCredentialIssuingService implements IssueApiTokenCredentialInterface
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    public function issue(string $identityId, \DateTimeImmutable $expiresAt): IssuedApiKey
    {
        $identifier = 'key_'.bin2hex(random_bytes(8));
        $secret = bin2hex(random_bytes(32));

        $this->commandBus->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identityId,
            identifier: $identifier,
            secret: $secret,
            expiresAt: $expiresAt->format('c'),
        ));

        return new IssuedApiKey($identifier, $secret);
    }
}
