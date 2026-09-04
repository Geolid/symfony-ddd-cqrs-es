<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\PrepareShipment;

use Shared\Application\Command\CommandInterface;

final readonly class PrepareShipment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
