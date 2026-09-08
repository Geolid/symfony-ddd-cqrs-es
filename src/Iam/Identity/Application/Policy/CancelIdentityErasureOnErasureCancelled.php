<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use Iam\Identity\Application\Command\CancelIdentityErasure\CancelIdentityErasure;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('iam.identity.cancel_identity_erasure_on_erasure_cancelled')]
final readonly class CancelIdentityErasureOnErasureCancelled
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ErasureCancelledIntegrationEvent::class)]
    public function __invoke(ErasureCancelledIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CancelIdentityErasure($event->identityId));
    }
}
