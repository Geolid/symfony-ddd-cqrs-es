<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\Shipment;

use Fulfilment\Shipping\Application\ShipmentStatus;

final readonly class ShipmentResult
{
    public function __construct(
        public string $id,
        public string $reference,
        public ShipmentStatus $status,
        public ?string $trackingNumber,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $manifestedAt,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
        public ?\DateTimeImmutable $cancelledAt,
    ) {
    }
}
