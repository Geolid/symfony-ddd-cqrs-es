<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\Command\EraseBuyer\EraseBuyer;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.buyer.erase_buyer_on_erasure_approved')]
final readonly class EraseBuyerOnErasureApproved
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
    #[Subscribe(ErasureApprovedIntegrationEvent::class)]
    public function __invoke(ErasureApprovedIntegrationEvent $event): void
    {
        $buyerId = BuyerId::forIdentity($event->identityId);

        if (!$this->repository->has($buyerId)) {
            return;
        }

        $this->commandBus->dispatch(new EraseBuyer($buyerId->toString()));
    }
}
