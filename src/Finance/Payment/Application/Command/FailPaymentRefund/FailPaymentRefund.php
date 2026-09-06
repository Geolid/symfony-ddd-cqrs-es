<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\FailPaymentRefund;

use Shared\Application\Command\CommandInterface;

final readonly class FailPaymentRefund implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $refundId,
    ) {
    }
}
