<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RequestOrderPayment;

use Shared\Application\Command\CommandInterface;

final readonly class RequestOrderPayment implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $customerId,
        public ?string $buyerAddress,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
    ) {
    }
}
