<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\CancelOrderPayment\CancelOrderPayment;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.cancel_order_payment_on_order_cancelled')]
final readonly class CancelOrderPaymentOnOrderCancelled
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderCancelled::class)]
    public function __invoke(OrderCancelled $event): void
    {
        $this->commandBus->dispatch(new CancelOrderPayment(
            OrderPaymentId::forOrder($event->id)->toString(),
        ));
    }
}
