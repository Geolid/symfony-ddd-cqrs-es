<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Domain\Event\OrderPaymentVoided;
use Shared\Application\Policy;

#[Policy('sales.order.void_order_payment_on_order_payment_voided')]
final readonly class VoidOrderPaymentOnOrderPaymentVoided
{
    public function __construct(private PaymentGatewayInterface $paymentGateway)
    {
    }

    #[Subscribe(OrderPaymentVoided::class)]
    public function __invoke(OrderPaymentVoided $event): void
    {
        $this->paymentGateway->void($event->reference);
    }
}
