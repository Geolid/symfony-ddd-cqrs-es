<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Sales\Order\Application\OrderPaymentStatus;
use Shared\Application\Exception\ApplicationExceptionInterface;

final class UnsupportedOrderPaymentStatusException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forStatus(OrderPaymentStatus $status): self
    {
        return new self(\sprintf('No reconciler supports order payment status "%s".', $status->value));
    }
}
