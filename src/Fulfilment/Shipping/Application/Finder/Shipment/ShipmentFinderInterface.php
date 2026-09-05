<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\Shipment;

use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<ShipmentResult>
 */
interface ShipmentFinderInterface extends IterableFinderInterface
{
    /**
     * @throws ShipmentResultNotFoundException
     */
    public function ofId(string $id): ShipmentResult;

    /**
     * @throws ShipmentResultNotFoundException
     */
    public function ofTrackingNumber(string $trackingNumber): ShipmentResult;

    public function ofReferenceOrNull(string $reference): ?ShipmentResult;

    public function byStatus(ShipmentStatus ...$statuses): static;

    public function stalledBefore(\DateTimeImmutable $cutoff): static;
}
