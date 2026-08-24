<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Finder\Shipment;

use Fulfilment\Shipment\Application\Status\ShipmentStatus;

final readonly class ShipmentResult
{
    public function __construct(
        public string $id,
        public string $orderId,
        public ShipmentStatus $status,
        public ?string $trackingReference,
        public ?string $returnTrackingReference,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $manifestedAt,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
        public ?\DateTimeImmutable $cancelledAt,
        public ?\DateTimeImmutable $returnDispatchedAt,
        public ?\DateTimeImmutable $returnReceivedAt,
        public ?\DateTimeImmutable $returnApprovedAt,
        public ?\DateTimeImmutable $returnRejectedAt,
        public ?string $returnRejectionReason,
    ) {
    }
}
