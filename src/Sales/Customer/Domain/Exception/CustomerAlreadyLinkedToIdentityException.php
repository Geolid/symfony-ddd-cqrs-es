<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Exception;

use Sales\Customer\Domain\CustomerId;

final class CustomerAlreadyLinkedToIdentityException extends \DomainException
{
    public static function forId(CustomerId $id): self
    {
        return new self(\sprintf('Customer "%s" is already linked to an identity.', $id->toString()));
    }
}
