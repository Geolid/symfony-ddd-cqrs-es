<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Carrier;

enum CarrierGatewayStatus: string
{
    case REQUESTED = 'requested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';

    public function isDispatched(): bool
    {
        return self::DISPATCHED === $this;
    }

    public function isDelivered(): bool
    {
        return self::DELIVERED === $this;
    }
}
