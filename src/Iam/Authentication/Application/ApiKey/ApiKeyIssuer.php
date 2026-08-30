<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\ApiKey;

use Iam\Authentication\Application\Command\IssueApiKeyCredential\IssueApiKeyCredential;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ApiKeyIssuer implements ApiKeyIssuerInterface
{
    public function __construct(
        private ApiKeyGenerator $apiKeyGenerator,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function issueFor(string $identityId, string $label): GeneratedApiKey
    {
        $apiKey = $this->apiKeyGenerator->generate();

        $this->commandBus->dispatch(new IssueApiKeyCredential(
            id: Uuid::uuid7()->toString(),
            identityId: $identityId,
            label: $label,
            keyId: $apiKey->keyId,
            secret: $apiKey->secret,
        ));

        return $apiKey;
    }
}
