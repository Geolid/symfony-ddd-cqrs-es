<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

use Sales\Order\Domain\ValueObject\OrderId;

final class OrderNotCancellableException extends \DomainException
{
    public static function forId(OrderId $id): self
    {
        return new self(\sprintf('Order "%s" can no longer be cancelled.', $id->toString()));
    }
}
