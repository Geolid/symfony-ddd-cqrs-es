<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Checkout\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class BuyerErasureRequestedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $buyerId): self
    {
        return new self(\sprintf('Buyer "%s" has an erasure request pending.', $buyerId));
    }
}
