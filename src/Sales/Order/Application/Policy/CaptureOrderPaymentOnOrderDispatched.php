<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\CaptureOrderPayment\CaptureOrderPayment;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.capture_order_payment_on_order_dispatched')]
final readonly class CaptureOrderPaymentOnOrderDispatched
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderDispatched::class)]
    public function __invoke(OrderDispatched $event): void
    {
        $this->commandBus->dispatch(new CaptureOrderPayment(
            OrderPaymentId::forOrder($event->id)->toString(),
        ));
    }
}
