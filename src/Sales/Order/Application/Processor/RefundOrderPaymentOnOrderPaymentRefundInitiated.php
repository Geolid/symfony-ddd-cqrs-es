<?php

declare(strict_types=1);

namespace Sales\Order\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Domain\Event\OrderPaymentRefundInitiated;
use Shared\Application\Processor\Processor;

#[Processor('sales.order.refund_order_payment_on_order_payment_refund_initiated')]
final readonly class RefundOrderPaymentOnOrderPaymentRefundInitiated
{
    public function __construct(private PaymentGatewayInterface $paymentGateway)
    {
    }

    #[Subscribe(OrderPaymentRefundInitiated::class)]
    public function __invoke(OrderPaymentRefundInitiated $event): void
    {
        $this->paymentGateway->refund($event->reference);
    }
}
