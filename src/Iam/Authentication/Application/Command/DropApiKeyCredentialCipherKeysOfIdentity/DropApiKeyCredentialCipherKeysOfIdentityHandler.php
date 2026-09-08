<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\DropApiKeyCredentialCipherKeysOfIdentity;

use Iam\Authentication\Application\Command\DropApiKeyCredentialCipherKey\DropApiKeyCredentialCipherKey;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[CommandHandler]
final readonly class DropApiKeyCredentialCipherKeysOfIdentityHandler
{
    public function __construct(
        private ApiKeyCredentialFinderInterface $apiKeyCredentialFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(DropApiKeyCredentialCipherKeysOfIdentity $command): void
    {
        foreach ($this->apiKeyCredentialFinder->byIdentity($command->identityId) as $apiKeyCredential) {
            $this->commandBus->dispatch(new DropApiKeyCredentialCipherKey($apiKeyCredential->id));
        }
    }
}
