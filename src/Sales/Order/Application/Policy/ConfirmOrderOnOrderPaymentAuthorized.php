<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Order\Domain\Event\OrderPaymentAuthorized;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy\Policy;

#[Policy('sales.order.confirm_order_on_order_payment_authorized')]
final readonly class ConfirmOrderOnOrderPaymentAuthorized
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderPaymentAuthorized::class)]
    public function __invoke(OrderPaymentAuthorized $event): void
    {
        $this->commandBus->dispatch(new ConfirmOrder($event->orderId));
    }
}
