<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Application\Finder\OrderTracking;

use Shared\Application\Query\Result\ResultInterface;

final readonly class OrderTrackingResult implements ResultInterface
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public string $status,
        public \DateTimeImmutable $placedAt,
    ) {
    }
}
