<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Policy;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\Command\EraseBuyer\EraseBuyer;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.buyer.erase_buyer_on_identity_erased')]
final readonly class EraseBuyerOnIdentityErased
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
    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        if (!$this->repository->has(BuyerId::fromString($event->identityId))) {
            return;
        }

        $this->commandBus->dispatch(new EraseBuyer($event->identityId));
    }
}
