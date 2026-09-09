<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('iam.identity.erase_identity_on_erasure_approved')]
final readonly class EraseIdentityOnErasureApproved
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ErasureApprovedIntegrationEvent::class)]
    public function __invoke(ErasureApprovedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new EraseIdentity($event->identityId));
    }
}
