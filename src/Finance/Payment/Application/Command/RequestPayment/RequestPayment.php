<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RequestPayment;

use Shared\Application\Command\CommandInterface;

final readonly class RequestPayment implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
    ) {
    }
}
