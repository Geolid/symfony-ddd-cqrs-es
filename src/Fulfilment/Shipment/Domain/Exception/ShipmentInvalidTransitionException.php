<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Exception;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;

final class ShipmentInvalidTransitionException extends \DomainException
{
    public static function cannotManifest(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot manifest shipment "%s" with status "%s".', $id->toString(), $current->value));
    }

    public static function cannotDispatch(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot dispatch shipment "%s" with status "%s".', $id->toString(), $current->value));
    }

    public static function cannotDeliver(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot deliver shipment "%s" with status "%s".', $id->toString(), $current->value));
    }

    public static function cannotRequestReturn(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot request a return for shipment "%s" with status "%s".', $id->toString(), $current->value));
    }

    public static function cannotManifestReturn(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot manifest a return for shipment "%s" with status "%s".', $id->toString(), $current->value));
    }

    public static function cannotDispatchReturn(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot dispatch the return of shipment "%s" with status "%s".', $id->toString(), $current->value));
    }

    public static function cannotReceiveReturn(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot receive a return for shipment "%s" with status "%s".', $id->toString(), $current->value));
    }

    public static function cannotApproveReturn(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot approve the return of shipment "%s" with status "%s".', $id->toString(), $current->value));
    }

    public static function cannotRejectReturn(ShipmentId $id, ShipmentState $current): self
    {
        return new self(\sprintf('Cannot reject the return of shipment "%s" with status "%s".', $id->toString(), $current->value));
    }
}
