<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Checkout\PaymentGatewayInterface;
use Finance\Payment\Domain\Event\PaymentRefundInitiated;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Policy;

#[Policy('finance.payment.refund_payment_on_payment_refund_initiated')]
final readonly class RefundPaymentOnPaymentRefundInitiated
{
    public function __construct(private PaymentGatewayInterface $paymentGateway)
    {
    }

    #[Subscribe(PaymentRefundInitiated::class)]
    public function __invoke(PaymentRefundInitiated $event): void
    {
        $this->paymentGateway->refund($event->reference);
    }
}
