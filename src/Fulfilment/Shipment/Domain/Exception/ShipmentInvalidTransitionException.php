<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;

final class ShipmentInvalidTransitionException extends \DomainException
{
    public static function cannotDispatch(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot dispatch shipment with status "%s".', $current->value));
    }

    public static function cannotDeliver(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot deliver shipment with status "%s".', $current->value));
    }

    public static function cannotManifest(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot manifest shipment with status "%s".', $current->value));
    }
}
