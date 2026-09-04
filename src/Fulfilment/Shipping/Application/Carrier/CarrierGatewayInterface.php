<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Carrier;

use Shared\Domain\ValueObject\PostalAddress;

interface CarrierGatewayInterface
{
    /**
     * @return string the carrier's own tracking reference
     */
    public function manifest(string $shipmentId, PostalAddress $origin, PostalAddress $destination): string;

    public function checkStatus(string $reference): CarrierGatewayStatus;
}
