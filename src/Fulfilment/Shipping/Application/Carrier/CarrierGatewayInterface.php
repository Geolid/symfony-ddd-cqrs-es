<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Carrier;

use Fulfilment\Shipping\Application\Carrier\Exception\CarrierGatewayException;
use Shared\Domain\ValueObject\PostalAddress;

interface CarrierGatewayInterface
{
    /**
     * @return string the carrier's own tracking reference
     *
     * @throws CarrierGatewayException
     */
    public function manifest(string $shipmentId, PostalAddress $origin, PostalAddress $destination): string;

    /**
     * @throws CarrierGatewayException
     */
    public function checkStatus(string $reference): CarrierGatewayStatus;
}
