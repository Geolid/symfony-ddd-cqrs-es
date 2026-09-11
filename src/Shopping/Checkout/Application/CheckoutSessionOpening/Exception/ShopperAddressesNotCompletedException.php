<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ShopperAddressesNotCompletedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $shopperId): self
    {
        return new self(\sprintf('Shopper "%s" has not completed its shipping/billing addresses.', $shopperId));
    }
}
