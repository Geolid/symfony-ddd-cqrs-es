<?php

declare(strict_types=1);

namespace Shipping\Shipment\Domain\Repository;

use Shipping\Shipment\Domain\Exception\ShipmentNotFoundException;
use Shipping\Shipment\Domain\Shipment;
use Shipping\Shipment\Domain\ShipmentId;

interface ShipmentRepositoryInterface
{
    public function has(ShipmentId $id): bool;

    /**
     * @throws ShipmentNotFoundException
     */
    public function load(ShipmentId $id): Shipment;

    public function save(Shipment $shipment): void;
}
