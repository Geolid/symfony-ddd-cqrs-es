<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\PSP\PaymentGatewayException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Domain\Event\PaymentRefundRequired;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Policy;

#[Policy('finance.payment.refund_payment_on_payment_refund_required')]
final readonly class RefundPaymentOnPaymentRefundRequired
{
    public function __construct(private PaymentGatewayInterface $paymentGateway)
    {
    }

    /**
     * @throws PaymentGatewayException
     */
    #[Subscribe(PaymentRefundRequired::class)]
    public function __invoke(PaymentRefundRequired $event): void
    {
        $this->paymentGateway->refund($event->reference);
    }
}
