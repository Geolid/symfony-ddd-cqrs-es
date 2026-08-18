<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Validator\Constraints as Assert;

final class PrepareShipmentInput
{
    #[Argument(description: 'ID of the Shipment to prepare')]
    #[Assert\Uuid]
    public string $shipmentId;
}
