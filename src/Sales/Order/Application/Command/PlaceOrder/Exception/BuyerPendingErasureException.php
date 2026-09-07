<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class BuyerPendingErasureException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $buyerId): self
    {
        return new self(\sprintf('Buyer "%s" has a pending account erasure request.', $buyerId));
    }
}
