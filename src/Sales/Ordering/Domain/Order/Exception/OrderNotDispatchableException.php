<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Exception;

use Sales\Ordering\Domain\Order\ValueObject\OrderId;

final class OrderNotDispatchableException extends \DomainException
{
    public static function cannotDispatch(OrderId $id): self
    {
        return new self(\sprintf('Order "%s" cannot be dispatched before it is paid.', $id->toString()));
    }
}
