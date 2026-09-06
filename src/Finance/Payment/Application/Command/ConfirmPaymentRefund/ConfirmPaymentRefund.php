<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\ConfirmPaymentRefund;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmPaymentRefund implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $refundId,
    ) {
    }
}
