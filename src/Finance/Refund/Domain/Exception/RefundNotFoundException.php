<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class RefundNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Refund "%s" not found.', $id));
    }
}
