<?php

declare(strict_types=1);

namespace Sales\Order\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Domain\Event\OrderPaymentRefundRequested;
use Shared\Application\Processor\Processor;

#[Processor('sales.order.refund_order_payment_on_order_payment_refund_requested')]
final readonly class RefundOrderPaymentOnOrderPaymentRefundRequested
{
    public function __construct(private PaymentGatewayInterface $paymentGateway)
    {
    }

    #[Subscribe(OrderPaymentRefundRequested::class)]
    public function __invoke(OrderPaymentRefundRequested $event): void
    {
        $this->paymentGateway->refund($event->reference);
    }
}
