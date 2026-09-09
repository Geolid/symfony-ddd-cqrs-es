<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Exception;

use Sales\Ordering\Domain\Order\ValueObject\OrderId;

final class OrderBelongsToAnotherBuyerException extends \DomainException
{
    public static function forId(OrderId $id): self
    {
        return new self(\sprintf('Order "%s" does not belong to that buyer.', $id->toString()));
    }
}
