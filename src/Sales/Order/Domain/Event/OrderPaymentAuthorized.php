<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.payment.authorized')]
final readonly class OrderPaymentAuthorized
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $authorizedAt,
    ) {
    }
}
