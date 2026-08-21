<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential;

use Iam\Authentication\Application\Command\IssueApiKeyCredential\IssueApiKeyCredential;
use Iam\Authentication\Application\Exception\AuthenticatableIdentityResultNotFoundException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ApiKeyIssuer implements ApiKeyIssuerInterface
{
    public function __construct(
        private ApiKeyGeneratorInterface $apiKeyGenerator,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws AuthenticatableIdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     * @throws LabelAlreadyTakenException
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
