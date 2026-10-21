<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Exception;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Shared\Domain\ValueObject\ErasureState;

final class CustomerInvalidStateException extends \DomainException
{
    public static function cannotErase(CustomerId $id, ErasureState $state): self
    {
        return new self(\sprintf('Customer "%s" cannot be erased while in state "%s".', $id->toString(), $state->value));
    }
}
