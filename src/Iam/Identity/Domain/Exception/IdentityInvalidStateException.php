<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;
use Shared\Domain\ValueObject\ErasureState;

final class IdentityInvalidStateException extends \DomainException
{
    public static function cannotErase(IdentityId $id, ErasureState $state): self
    {
        return new self(\sprintf('Identity "%s" cannot be erased while in state "%s".', $id->toString(), $state->value));
    }
}
