<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\Command\RequestBuyerErasure\RequestBuyerErasure;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.buyer.request_buyer_erasure_on_erasure_requested')]
final readonly class RequestBuyerErasureOnErasureRequested
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
    #[Subscribe(ErasureRequestedIntegrationEvent::class)]
    public function __invoke(ErasureRequestedIntegrationEvent $event): void
    {
        $buyerId = BuyerId::forIdentity($event->identityId);

        if (!$this->repository->has($buyerId)) {
            return;
        }

        $this->commandBus->dispatch(new RequestBuyerErasure($buyerId->toString()));
    }
}
