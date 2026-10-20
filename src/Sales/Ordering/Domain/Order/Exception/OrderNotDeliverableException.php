<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Exception;

use Sales\Ordering\Domain\Order\ValueObject\OrderId;

final class OrderNotDeliverableException extends \DomainException
{
    public static function cannotDeliver(OrderId $id): self
    {
        return new self(\sprintf('Order "%s" cannot be delivered before it is dispatched.', $id->toString()));
    }
}
