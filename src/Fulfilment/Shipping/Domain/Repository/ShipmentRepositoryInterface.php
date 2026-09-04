<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Repository;

use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;

interface ShipmentRepositoryInterface
{
    public function has(ShipmentId $id): bool;

    /**
     * @throws ShipmentNotFoundException
     */
    public function load(ShipmentId $id): Shipment;

    /**
     * @throws ShipmentAlreadyExistsException
     */
    public function save(Shipment $shipment): void;
}
