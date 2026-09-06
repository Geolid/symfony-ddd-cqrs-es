<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RequestPaymentRefund;

use Shared\Application\Command\CommandInterface;

final readonly class RequestPaymentRefund implements CommandInterface
{
    public function __construct(
        public string $paymentId,
        public string $refundId,
    ) {
    }
}
