<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Iam\Identity\Application\Command\RequestIdentityErasure\RequestIdentityErasure;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('iam.identity.request_identity_erasure_on_erasure_requested')]
final readonly class RequestIdentityErasureOnErasureRequested
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ErasureRequestedIntegrationEvent::class)]
    public function __invoke(ErasureRequestedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new RequestIdentityErasure($event->identityId));
    }
}
