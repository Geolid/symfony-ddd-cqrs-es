<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Exception;

use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\OrderState;
use Shared\Domain\Exception\AggregateStateInconsistentException;

final class OrderStateInconsistentException extends AggregateStateInconsistentException
{
    public static function cannotPay(OrderId $id, OrderState $state): self
    {
        return new self(self::describe('Order', $id->toString(), 'paid', $state->value));
    }

    public static function cannotDispatch(OrderId $id, OrderState $state): self
    {
        return new self(self::describe('Order', $id->toString(), 'dispatched', $state->value));
    }

    public static function cannotDeliver(OrderId $id, OrderState $state): self
    {
        return new self(self::describe('Order', $id->toString(), 'delivered', $state->value));
    }
}
