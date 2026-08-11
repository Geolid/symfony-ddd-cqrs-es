<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Processor;

use Iam\Identity\Application\Command\RevokeApiTokenCredentialsForIdentity\RevokeApiTokenCredentialsForIdentity;
use Iam\Identity\Domain\Event\IdentityErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('iam.identity.revoke_api_token_credentials_on_identity_erased')]
final readonly class RevokeApiTokenCredentialsOnIdentityErased
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(IdentityErased::class)]
    public function __invoke(IdentityErased $event): void
    {
        $this->commandBus->dispatch(new RevokeApiTokenCredentialsForIdentity($event->id));
    }
}
