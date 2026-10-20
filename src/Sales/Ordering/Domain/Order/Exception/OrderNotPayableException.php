<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Exception;

use Sales\Ordering\Domain\Order\ValueObject\OrderId;

final class OrderNotPayableException extends \DomainException
{
    public static function cannotPay(OrderId $id): self
    {
        return new self(\sprintf('Order "%s" cannot be paid before it is prepared.', $id->toString()));
    }
}
