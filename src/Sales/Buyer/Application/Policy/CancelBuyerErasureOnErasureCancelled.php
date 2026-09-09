<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\Command\CancelBuyerErasure\CancelBuyerErasure;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.buyer.cancel_buyer_erasure_on_erasure_cancelled')]
final readonly class CancelBuyerErasureOnErasureCancelled
{
    public function __construct(
        private BuyerRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ErasureCancelledIntegrationEvent::class)]
    public function __invoke(ErasureCancelledIntegrationEvent $event): void
    {
        $buyerId = BuyerId::forIdentity($event->identityId);

        if (!$this->repository->has($buyerId)) {
            return;
        }

        $this->commandBus->dispatch(new CancelBuyerErasure($buyerId->toString()));
    }
}
