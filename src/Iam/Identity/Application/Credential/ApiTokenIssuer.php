<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Credential;

use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Application\Security\ApiTokenGeneratorInterface;
use Iam\Identity\Application\Security\GeneratedApiToken;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ApiTokenIssuer implements ApiTokenIssuerInterface
{
    public function __construct(
        private ApiTokenGeneratorInterface $apiTokenGenerator,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityNotActiveException
     * @throws LabelAlreadyTakenException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function issueFor(string $identityId, string $label, string $expiresAt): GeneratedApiToken
    {
        $apiKey = $this->apiTokenGenerator->generate();

        $this->commandBus->dispatch(new IssueApiTokenCredential(
            id: Uuid::uuid7()->toString(),
            identityId: $identityId,
            identifier: $apiKey->identifier,
            secret: $apiKey->secret,
            label: $label,
            expiresAt: $expiresAt,
        ));

        return $apiKey;
    }
}
