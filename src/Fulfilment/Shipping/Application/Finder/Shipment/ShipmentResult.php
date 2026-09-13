<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\Shipment;

use Fulfilment\Shipping\Application\ShipmentStatus;
use Shared\Application\ErasureStatus;

final readonly class ShipmentResult
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $customerId,
        public ShipmentStatus $status,
        public PostalAddressResult $origin,
        public PostalAddressResult $destination,
        public ?string $trackingNumber,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $manifestedAt,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
        public ?\DateTimeImmutable $cancelledAt,
        public ErasureStatus $erasureStatus,
    ) {
    }
}
