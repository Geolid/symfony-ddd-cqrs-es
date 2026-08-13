<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OutdatedOrderException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forReason(string $reason, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('The order could not be placed: %s', $reason),
            previous: $previous,
        );
    }
}
