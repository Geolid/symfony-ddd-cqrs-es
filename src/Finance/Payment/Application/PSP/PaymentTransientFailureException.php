<?php

declare(strict_types=1);

namespace Finance\Payment\Application\PSP;

final class PaymentTransientFailureException extends PaymentGatewayException
{
    public static function forReason(string $reason, ?\Throwable $previous = null): self
    {
        return new self($reason, previous: $previous);
    }
}
