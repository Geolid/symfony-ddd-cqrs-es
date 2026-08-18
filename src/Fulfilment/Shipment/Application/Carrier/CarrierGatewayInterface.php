<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier;

use Shared\Domain\ValueObject\PostalAddress;

interface CarrierGatewayInterface
{
    /**
     * @return string the carrier's own tracking reference
     */
    public function requestPickup(string $shipmentId, PostalAddress $deliveryAddress): string;

    /**
     * @return string the carrier's own tracking reference for the return leg
     */
    public function requestReturnPickup(string $shipmentId, PostalAddress $pickupAddress): string;
}
