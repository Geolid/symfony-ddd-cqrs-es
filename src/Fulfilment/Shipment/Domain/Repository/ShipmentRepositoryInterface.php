<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Repository;

use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface ShipmentRepositoryInterface
{
    public function has(ShipmentId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(ShipmentId $id): Shipment;

    public function save(Shipment $shipment): void;
}
