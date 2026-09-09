<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Exception;

use Sales\Ordering\Domain\ValueObject\OrderId;

final class OrderWithoutLineException extends \DomainException
{
    public static function forId(OrderId $id): self
    {
        return new self(\sprintf('Order "%s" carries no line.', $id->toString()));
    }
}
