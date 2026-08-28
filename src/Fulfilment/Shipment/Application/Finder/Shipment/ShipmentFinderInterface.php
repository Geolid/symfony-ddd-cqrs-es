<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Finder\Shipment;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<ShipmentResult>
 */
interface ShipmentFinderInterface extends IterableFinderInterface
{
    /**
     * @throws ShipmentResultNotFoundException
     */
    public function ofTrackingReference(string $trackingReference): ShipmentResult;

    /**
     * @throws ShipmentResultNotFoundException
     */
    public function ofReturnTrackingReference(string $returnTrackingReference): ShipmentResult;

    public function byStatus(ShipmentStatus ...$statuses): static;

    public function byCustomer(string $customerId): static;

    public function stalledBefore(\DateTimeImmutable $cutoff): static;
}
