<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\IdentityId;

final class IdentityNotSuspendedException extends \DomainException
{
    public static function forId(IdentityId $id): self
    {
        return new self(\sprintf('The identity "%s" is not suspended.', $id->toString()));
    }
}
