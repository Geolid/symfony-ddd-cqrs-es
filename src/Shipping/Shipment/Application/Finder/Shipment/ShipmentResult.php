<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Finder\Shipment;

final readonly class ShipmentResult
{
    public function __construct(
        public string $id,
        public string $orderId,
        public ?string $customerId,
        public ?int $orderTotalInCents,
        public string $status,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
        public ?\DateTimeImmutable $orderCancelledAt,
    ) {
    }
}
