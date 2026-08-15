<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class OrderPaymentResultNotFoundException extends ResultNotFoundException
{
    public static function forReference(string $reference): self
    {
        return new self(\sprintf('OrderPayment referenced "%s" not found.', $reference));
    }
}
