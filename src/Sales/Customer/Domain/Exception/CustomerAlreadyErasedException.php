<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Exception;

use Sales\Customer\Domain\ValueObject\CustomerId;

final class CustomerAlreadyErasedException extends \DomainException
{
    public static function forId(CustomerId $id): self
    {
        return new self(\sprintf('Customer with ID "%s" is already erased.', $id->toString()));
    }
}
