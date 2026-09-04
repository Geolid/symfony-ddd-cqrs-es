<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class BuyerNotRegisteredException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $buyerId): self
    {
        return new self(\sprintf('Buyer "%s" is not registered.', $buyerId));
    }
}
