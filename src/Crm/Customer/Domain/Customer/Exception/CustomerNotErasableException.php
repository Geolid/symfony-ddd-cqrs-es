<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Exception;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;

final class CustomerNotErasableException extends \DomainException
{
    public static function cannotErase(CustomerId $id): self
    {
        return new self(\sprintf('Customer "%s" cannot be erased before its erasure is requested.', $id->toString()));
    }
}
