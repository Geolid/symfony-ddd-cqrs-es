<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Policy;

use AfterSales\Return\Application\Command\ReceiveWithdrawal\ReceiveWithdrawal;
use AfterSales\Return\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('after_sales.return.receive_withdrawal_on_shipment_delivered')]
final readonly class ReceiveWithdrawalOnShipmentDelivered
{
    public function __construct(
        private WithdrawalRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentDeliveredIntegrationEvent::class)]
    public function __invoke(ShipmentDeliveredIntegrationEvent $event): void
    {
        $id = WithdrawalId::fromString($event->reference);

        if (!$this->repository->has($id)) {
            return;
        }

        $withdrawal = $this->repository->load($id);

        $this->commandBus->dispatch(new ReceiveWithdrawal($withdrawal->orderId));
    }
}
