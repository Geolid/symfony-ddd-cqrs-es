<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

use Sales\Order\Domain\ValueObject\OrderPaymentId;

final class OrderPaymentNotFoundException extends \DomainException
{
    public static function forId(OrderPaymentId $id): self
    {
        return new self(\sprintf('OrderPayment with ID "%s" not found.', $id->toString()));
    }
}
