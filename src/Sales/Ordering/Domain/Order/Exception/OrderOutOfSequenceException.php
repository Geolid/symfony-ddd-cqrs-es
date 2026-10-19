<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Exception;

use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\OrderState;

final class OrderOutOfSequenceException extends \DomainException
{
    public static function forTarget(OrderId $id, OrderState $target): self
    {
        return new self(\sprintf('Order "%s" has not yet reached the state preceding "%s".', $id->toString(), $target->value));
    }
}
