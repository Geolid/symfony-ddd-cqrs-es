<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrphanedOrdersOfCustomer;

use Sales\Order\Application\Command\CancelOrphanedOrder\CancelOrphanedOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[CommandHandler]
final readonly class CancelOrphanedOrdersOfCustomerHandler
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(CancelOrphanedOrdersOfCustomer $command): void
    {
        foreach ($this->orderFinder->byCustomer($command->customerId) as $order) {
            $this->commandBus->dispatch(new CancelOrphanedOrder($order->id, $command->customerId));
        }
    }
}
