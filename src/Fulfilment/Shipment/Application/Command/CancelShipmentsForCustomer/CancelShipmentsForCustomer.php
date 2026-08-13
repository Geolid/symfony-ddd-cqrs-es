<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\CancelShipmentsForCustomer;

use Shared\Application\Command\CommandInterface;

final readonly class CancelShipmentsForCustomer implements CommandInterface
{
    public function __construct(public string $customerId)
    {
    }
}
