<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;

final class IdentityNotActiveException extends \DomainException
{
    public static function forId(IdentityId $id): self
    {
        return new self(\sprintf('The identity "%s" is not active.', $id->toString()));
    }
}
