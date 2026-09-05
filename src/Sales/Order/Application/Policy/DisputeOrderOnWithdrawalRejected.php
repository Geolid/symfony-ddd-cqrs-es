<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRejected\WithdrawalRejectedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\DisputeOrder\DisputeOrder;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.dispute_order_on_withdrawal_rejected')]
final readonly class DisputeOrderOnWithdrawalRejected
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
    #[Subscribe(WithdrawalRejectedIntegrationEvent::class)]
    public function __invoke(WithdrawalRejectedIntegrationEvent $event): void
    {
        if (!$this->repository->has(OrderId::fromString($event->orderId))) {
            return;
        }

        $this->commandBus->dispatch(new DisputeOrder($event->orderId));
    }
}
