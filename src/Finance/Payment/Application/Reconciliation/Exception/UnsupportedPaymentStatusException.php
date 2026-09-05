<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Reconciliation\Exception;

use Finance\Payment\Application\PaymentStatus;
use Shared\Application\Exception\ApplicationExceptionInterface;

final class UnsupportedPaymentStatusException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forStatus(PaymentStatus $status): self
    {
        return new self(\sprintf('No reconciler supports order payment status "%s".', $status->value));
    }
}
