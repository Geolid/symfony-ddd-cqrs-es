<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class OrderPaymentAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('OrderPayment with ID "%s" already exists.', $id));
    }
}
