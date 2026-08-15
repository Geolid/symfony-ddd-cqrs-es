<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class CustomerNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Customer with ID "%s" not found.', $id));
    }
}
