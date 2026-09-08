<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Policy;

use Iam\Authentication\Application\Command\DropApiKeyCredentialCipherKeysOfIdentity\DropApiKeyCredentialCipherKeysOfIdentity;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('iam.authentication.drop_api_key_credential_cipher_keys_on_identity_erased')]
final readonly class DropApiKeyCredentialCipherKeysOnIdentityErased
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new DropApiKeyCredentialCipherKeysOfIdentity($event->identityId));
    }
}
