<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OrderResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Order with ID "%s" not found.', $id));
    }
}
