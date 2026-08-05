<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OrderPaymentAlreadyCapturedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrderId(string $orderId): self
    {
        return new self(\sprintf('The payment for order "%s" has already been captured.', $orderId));
    }
}
