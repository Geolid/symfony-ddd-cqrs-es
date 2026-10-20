<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Exception;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Shared\Domain\ValueObject\ErasureState;
use Shared\Domain\Exception\AggregateStateInconsistentException;

final class CustomerStateInconsistentException extends AggregateStateInconsistentException
{
    public static function cannotErase(CustomerId $id, ErasureState $state): self
    {
        return new self(self::describe('Customer', $id->toString(), 'erased', $state->value));
    }
}
