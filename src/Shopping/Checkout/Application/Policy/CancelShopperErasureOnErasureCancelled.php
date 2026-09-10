<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\CancelShopperErasure\CancelShopperErasure;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

#[Policy('shopping.checkout.cancel_shopper_erasure_on_erasure_cancelled')]
final readonly class CancelShopperErasureOnErasureCancelled
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
    #[Subscribe(ErasureCancelledIntegrationEvent::class)]
    public function __invoke(ErasureCancelledIntegrationEvent $event): void
    {
        $shopperId = ShopperId::forIdentity($event->identityId);

        if (!$this->repository->has($shopperId)) {
            return;
        }

        $this->commandBus->dispatch(new CancelShopperErasure($shopperId->toString()));
    }
}
