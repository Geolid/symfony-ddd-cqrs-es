<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class BuyerAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Buyer "%s" already exists.', $id));
    }
}
