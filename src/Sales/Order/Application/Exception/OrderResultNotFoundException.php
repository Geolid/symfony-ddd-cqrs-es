<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class OrderResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Order not found for criteria %s.', json_encode(['id' => $id], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)));
    }
}
