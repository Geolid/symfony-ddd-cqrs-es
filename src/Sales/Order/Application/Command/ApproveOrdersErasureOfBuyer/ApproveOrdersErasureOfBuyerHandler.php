<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\ApproveOrdersErasureOfBuyer;

use Sales\Order\Application\Command\ApproveOrderErasure\ApproveOrderErasure;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[CommandHandler]
final readonly class ApproveOrdersErasureOfBuyerHandler
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
    public function __invoke(ApproveOrdersErasureOfBuyer $command): void
    {
        foreach ($this->orderFinder->byBuyer($command->buyerId) as $order) {
            $this->commandBus->dispatch(new ApproveOrderErasure($order->id));
        }
    }
}
