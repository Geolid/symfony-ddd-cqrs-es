<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Manifest;

use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Manifest\Exception\ManifestDeniedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface ShipmentManifesterInterface
{
    /**
     * @return string the carrier's own tracking reference
     *
     * @throws ShipmentResultNotFoundException
     * @throws ManifestDeniedException
     * @throws ShipmentAlreadyTrackedException
     * @throws ShipmentInvalidTransitionException
     */
    public function manifest(string $shipmentId): string;
}
