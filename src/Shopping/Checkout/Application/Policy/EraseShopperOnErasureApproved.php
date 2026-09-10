<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\EraseShopper\EraseShopper;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

#[Policy('shopping.checkout.erase_shopper_on_erasure_approved')]
final readonly class EraseShopperOnErasureApproved
{
    public function __construct(
        private ShopperRepositoryInterface $repository,
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
        $shopperId = ShopperId::forIdentity($event->identityId);

        if (!$this->repository->has($shopperId)) {
            return;
        }

        $this->commandBus->dispatch(new EraseShopper($shopperId->toString()));
    }
}
