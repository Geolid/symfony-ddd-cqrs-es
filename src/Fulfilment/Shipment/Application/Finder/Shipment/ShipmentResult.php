<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Finder\Shipment;

use Shared\Application\Query\Result\ResultInterface;

final readonly class ShipmentResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public ?string $customerId,
        public ?int $orderTotalInCents,
        public string $status,
        public ?string $trackingReference,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
        public ?\DateTimeImmutable $orderCancelledAt,
    ) {
    }
}
