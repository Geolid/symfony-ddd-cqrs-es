<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\ReturnOrder\ReturnOrder;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.return_order_on_withdrawal_approved')]
final readonly class ReturnOrderOnWithdrawalApproved
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
    #[Subscribe(WithdrawalApprovedIntegrationEvent::class)]
    public function __invoke(WithdrawalApprovedIntegrationEvent $event): void
    {
        if (!$this->repository->has(OrderId::fromString($event->orderId))) {
            return;
        }

        $this->commandBus->dispatch(new ReturnOrder($event->orderId));
    }
}
