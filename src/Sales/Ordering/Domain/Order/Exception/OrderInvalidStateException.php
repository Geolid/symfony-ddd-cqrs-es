<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Exception;

use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\OrderState;

final class OrderInvalidStateException extends \DomainException
{
    public static function cannotPay(OrderId $id, OrderState $state): self
    {
        return new self(\sprintf('Order "%s" cannot be paid while in state "%s".', $id->toString(), $state->value));
    }

    public static function cannotCancel(OrderId $id, OrderState $state): self
    {
        return new self(\sprintf('Order "%s" cannot be cancelled while in state "%s".', $id->toString(), $state->value));
    }

    public static function cannotDispatch(OrderId $id, OrderState $state): self
    {
        return new self(\sprintf('Order "%s" cannot be dispatched while in state "%s".', $id->toString(), $state->value));
    }

    public static function cannotDeliver(OrderId $id, OrderState $state): self
    {
        return new self(\sprintf('Order "%s" cannot be delivered while in state "%s".', $id->toString(), $state->value));
    }
}
