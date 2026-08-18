<?php

declare(strict_types=1);

namespace Sales\Order\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\ReturnOrder\ReturnOrder;
use Sales\Order\Domain\Event\OrderPaymentRefunded;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('sales.order.return_order_on_order_payment_refunded')]
final readonly class ReturnOrderOnOrderPaymentRefunded
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderPaymentRefunded::class)]
    public function __invoke(OrderPaymentRefunded $event): void
    {
        $this->commandBus->dispatch(new ReturnOrder($event->orderId));
    }
}
