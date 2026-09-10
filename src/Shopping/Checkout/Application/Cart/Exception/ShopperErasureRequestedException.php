<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Cart\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ShopperErasureRequestedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $shopperId): self
    {
        return new self(\sprintf('Shopper "%s" has an erasure request pending.', $shopperId));
    }
}
