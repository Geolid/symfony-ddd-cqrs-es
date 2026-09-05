<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class WithdrawalRequestInProgressException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrder(string $orderId, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('A withdrawal request for order "%s" is already in progress.', $orderId),
            previous: $previous,
        );
    }
}
