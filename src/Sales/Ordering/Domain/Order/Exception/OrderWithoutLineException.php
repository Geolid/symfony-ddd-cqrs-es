<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Exception;

use Sales\Ordering\Domain\Order\ValueObject\OrderId;

final class OrderWithoutLineException extends \DomainException
{
    public static function forId(OrderId $id): self
    {
        return new self(\sprintf('Order "%s" carries no line.', $id->toString()));
    }
}
