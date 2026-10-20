<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;
use Shared\Domain\ValueObject\ErasureState;
use Shared\Domain\Exception\AggregateStateInconsistentException;

final class IdentityStateInconsistentException extends AggregateStateInconsistentException
{
    public static function cannotErase(IdentityId $id, ErasureState $state): self
    {
        return new self(self::describe('Identity', $id->toString(), 'erased', $state->value));
    }
}
