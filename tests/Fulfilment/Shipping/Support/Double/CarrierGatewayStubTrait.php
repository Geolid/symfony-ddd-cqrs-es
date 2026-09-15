<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Support\Double;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayStatus;

trait CarrierGatewayStubTrait
{
    private function carrierGatewayReturning(CarrierGatewayStatus $status): CarrierGatewayInterface
    {
        $carrierGateway = $this->createStub(CarrierGatewayInterface::class);
        $carrierGateway->method('checkStatus')->willReturn($status);

        return $carrierGateway;
    }
}
