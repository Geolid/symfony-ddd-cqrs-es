<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class CustomerAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Customer with ID "%s" already exists.', $id));
    }
}
