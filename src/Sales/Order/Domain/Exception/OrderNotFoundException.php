<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

use Sales\Order\Domain\ValueObject\OrderId;

final class OrderNotFoundException extends \DomainException
{
    public static function forId(OrderId $id): self
    {
        return new self(\sprintf('Order with ID "%s" not found.', $id->toString()));
    }
}
