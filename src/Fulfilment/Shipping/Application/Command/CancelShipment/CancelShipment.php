<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\CancelShipment;

use Shared\Application\Command\CommandInterface;

final readonly class CancelShipment implements CommandInterface
{
    public function __construct(public string $reference)
    {
    }
}
