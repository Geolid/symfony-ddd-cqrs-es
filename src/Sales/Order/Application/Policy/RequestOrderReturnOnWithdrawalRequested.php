<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\RequestOrderReturn\RequestOrderReturn;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.request_order_return_on_withdrawal_requested')]
final readonly class RequestOrderReturnOnWithdrawalRequested
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
    #[Subscribe(WithdrawalRequestedIntegrationEvent::class)]
    public function __invoke(WithdrawalRequestedIntegrationEvent $event): void
    {
        if (!$this->repository->has(OrderId::fromString($event->orderId))) {
            return;
        }

        $this->commandBus->dispatch(new RequestOrderReturn($event->orderId));
    }
}
