<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

enum ShipmentState: string
{
    case REQUESTED = 'requested';
    case PREPARED = 'prepared';
    case MANIFESTED = 'manifested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case RETURN_REQUESTED = 'return_requested';
    case RETURN_MANIFESTED = 'return_manifested';
    case RETURN_DISPATCHED = 'return_dispatched';
    case RETURN_RECEIVED = 'return_received';
    case RETURN_APPROVED = 'return_approved';
    case RETURN_REJECTED = 'return_rejected';

    public function isRequested(): bool
    {
        return self::REQUESTED === $this;
    }

    public function isPrepared(): bool
    {
        return self::PREPARED === $this;
    }

    public function isManifested(): bool
    {
        return self::MANIFESTED === $this;
    }

    public function isDispatched(): bool
    {
        return self::DISPATCHED === $this;
    }

    public function isDelivered(): bool
    {
        return self::DELIVERED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }

    public function isReturnRequested(): bool
    {
        return self::RETURN_REQUESTED === $this;
    }

    public function isReturnManifested(): bool
    {
        return self::RETURN_MANIFESTED === $this;
    }

    public function isReturnDispatched(): bool
    {
        return self::RETURN_DISPATCHED === $this;
    }

    public function isReturnManifestedOrLater(): bool
    {
        return \in_array($this, self::returnManifestedOrLater(), true);
    }

    public function isReturnDispatchedOrLater(): bool
    {
        return \in_array($this, self::returnDispatchedOrLater(), true);
    }

    public function isReturnReceived(): bool
    {
        return self::RETURN_RECEIVED === $this;
    }

    public function isReturnApproved(): bool
    {
        return self::RETURN_APPROVED === $this;
    }

    public function isReturnRejected(): bool
    {
        return self::RETURN_REJECTED === $this;
    }

    public function isCancellable(): bool
    {
        return \in_array($this, self::cancellableStates(), true);
    }

    /**
     * @return list<self>
     */
    private static function cancellableStates(): array
    {
        return [self::REQUESTED, self::PREPARED];
    }

    /**
     * @return list<self>
     */
    private static function returnManifestedOrLater(): array
    {
        return [self::RETURN_MANIFESTED, self::RETURN_DISPATCHED, self::RETURN_RECEIVED, self::RETURN_APPROVED, self::RETURN_REJECTED];
    }

    /**
     * @return list<self>
     */
    private static function returnDispatchedOrLater(): array
    {
        return [self::RETURN_DISPATCHED, self::RETURN_RECEIVED, self::RETURN_APPROVED, self::RETURN_REJECTED];
    }
}
