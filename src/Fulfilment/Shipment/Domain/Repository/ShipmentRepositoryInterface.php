<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Repository;

use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ShipmentId;

interface ShipmentRepositoryInterface
{
    public function has(ShipmentId $id): bool;

    /**
     * @throws ShipmentNotFoundException
     */
    public function load(ShipmentId $id): Shipment;

    public function save(Shipment $shipment): void;
}
