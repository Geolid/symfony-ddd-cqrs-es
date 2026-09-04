<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class BuyerNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Buyer "%s" not found.', $id));
    }
}
