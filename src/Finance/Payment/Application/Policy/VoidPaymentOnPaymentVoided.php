<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Checkout\PaymentGatewayInterface;
use Finance\Payment\Domain\Event\PaymentVoided;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Policy;

#[Policy('finance.payment.void_payment_on_payment_voided')]
final readonly class VoidPaymentOnPaymentVoided
{
    public function __construct(private PaymentGatewayInterface $paymentGateway)
    {
    }

    #[Subscribe(PaymentVoided::class)]
    public function __invoke(PaymentVoided $event): void
    {
        $this->paymentGateway->void($event->reference);
    }
}
