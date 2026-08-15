<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class OrderPaymentNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('OrderPayment with ID "%s" not found.', $id));
    }
}
