<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\Command\RequestShopperErasure\RequestShopperErasure;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

#[Policy('shopping.checkout.request_shopper_erasure_on_erasure_requested')]
final readonly class RequestShopperErasureOnErasureRequested
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
    #[Subscribe(ErasureRequestedIntegrationEvent::class)]
    public function __invoke(ErasureRequestedIntegrationEvent $event): void
    {
        $shopperId = ShopperId::forIdentity($event->identityId);

        if (!$this->repository->has($shopperId)) {
            return;
        }

        $this->commandBus->dispatch(new RequestShopperErasure($shopperId->toString()));
    }
}
