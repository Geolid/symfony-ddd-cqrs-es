<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class InspectShipmentReturnInput
{
    #[Argument(description: 'ID of the Shipment whose return was received')]
    #[Assert\Uuid]
    public string $shipmentId;

    #[Option(description: 'Approve the return (quality control passed)')]
    public bool $approve = false;

    #[Option(description: 'Reject the return, with a reason (quality control failed)')]
    public ?string $reject = null;
}
