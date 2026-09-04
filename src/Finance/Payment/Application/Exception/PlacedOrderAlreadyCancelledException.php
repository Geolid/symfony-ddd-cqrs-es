<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PlacedOrderAlreadyCancelledException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrder(string $orderId): self
    {
        return new self(\sprintf('Order "%s" is already cancelled.', $orderId));
    }
}
