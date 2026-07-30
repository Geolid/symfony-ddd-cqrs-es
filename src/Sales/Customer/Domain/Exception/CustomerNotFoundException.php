<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Exception;

use Sales\Customer\Domain\CustomerId;

final class CustomerNotFoundException extends \DomainException
{
    public static function forId(CustomerId $id): self
    {
        return new self(\sprintf('Customer with ID "%s" does not exist.', $id->toString()));
    }
}
