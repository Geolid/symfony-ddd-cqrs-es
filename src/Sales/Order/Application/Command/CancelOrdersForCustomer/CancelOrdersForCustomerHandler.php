<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrdersForCustomer;

use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[AsCommandHandler]
final readonly class CancelOrdersForCustomerHandler
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
    public function __invoke(CancelOrdersForCustomer $command): void
    {
        foreach ($this->orderFinder->byCustomer($command->customerId) as $order) {
            if ($order->status->isCancelled()) {
                continue;
            }

            $this->commandBus->dispatch(new CancelOrder($order->id, $command->customerId));
        }
    }
}
