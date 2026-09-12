<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class CustomerNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Customer "%s" not found.', $id));
    }
}
