<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentFailed\PaymentFailedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\AbortOrder\AbortOrder;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.abort_order_on_payment_failed')]
final readonly class AbortOrderOnPaymentFailed
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(PaymentFailedIntegrationEvent::class)]
    public function __invoke(PaymentFailedIntegrationEvent $event): void
    {
        if (!$this->repository->has(OrderId::fromString($event->orderId))) {
            return;
        }

        $this->commandBus->dispatch(new AbortOrder($event->orderId));
    }
}
