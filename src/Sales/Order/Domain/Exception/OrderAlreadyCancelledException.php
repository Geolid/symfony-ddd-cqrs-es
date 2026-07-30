<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

use Sales\Order\Domain\OrderId;

final class OrderAlreadyCancelledException extends \DomainException
{
    public static function forId(OrderId $id): self
    {
        return new self(\sprintf('Order with ID "%s" is already cancelled.', $id->toString()));
    }
}
