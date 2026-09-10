<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Checkout\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ShopperNotRegisteredException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $shopperId): self
    {
        return new self(\sprintf('Shopper "%s" is not registered.', $shopperId));
    }
}
