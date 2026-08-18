<?php

declare(strict_types=1);

namespace Sales\Order\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\RefundOrderPayment\RefundOrderPayment;
use Sales\Order\Domain\Event\OrderRefundStarted;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('sales.order.refund_order_payment_on_order_refund_started')]
final readonly class RefundOrderPaymentOnOrderRefundStarted
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderRefundStarted::class)]
    public function __invoke(OrderRefundStarted $event): void
    {
        $this->commandBus->dispatch(new RefundOrderPayment(
            OrderPaymentId::forOrder($event->id)->toString(),
        ));
    }
}
