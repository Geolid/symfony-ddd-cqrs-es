<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OrderPaymentResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forReference(string $reference): self
    {
        return new self(\sprintf('OrderPayment referenced "%s" not found.', $reference));
    }
}
