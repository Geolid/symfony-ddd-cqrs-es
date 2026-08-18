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

    public static function cannotManifestReturn(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot manifest a return for shipment with status "%s".', $current->value));
    }

    public static function cannotDispatchReturn(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot dispatch the return of shipment with status "%s".', $current->value));
    }

    public static function cannotReceiveReturn(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot receive a return for shipment with status "%s".', $current->value));
    }

    public static function cannotApproveReturn(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot approve the return of shipment with status "%s".', $current->value));
    }

    public static function cannotRejectReturn(ShipmentState $current): self
    {
        return new self(\sprintf('Cannot reject the return of shipment with status "%s".', $current->value));
    }
}
