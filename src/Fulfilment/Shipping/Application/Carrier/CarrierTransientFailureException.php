<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Carrier;

final class CarrierTransientFailureException extends CarrierGatewayException
{
    public static function forReason(string $reason, ?\Throwable $previous = null): self
    {
        return new self($reason, previous: $previous);
    }
}
