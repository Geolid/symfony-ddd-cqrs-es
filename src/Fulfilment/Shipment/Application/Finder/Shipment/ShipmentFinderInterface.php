<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Finder\Shipment;

use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<ShipmentResult>
 */
interface ShipmentFinderInterface extends CollectionFinderInterface
{
    /**
     * @throws ResultNotFoundException
     */
    public function ofTrackingReference(string $trackingReference): ShipmentResult;

    public function byStatus(string ...$statuses): static;

    public function byCustomer(string $customerId): static;
}
