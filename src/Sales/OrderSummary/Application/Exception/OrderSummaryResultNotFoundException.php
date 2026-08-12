<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OrderSummaryResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrderId(string $orderId): self
    {
        return new self(\sprintf('Summary for order "%s" not found.', $orderId));
    }
}
