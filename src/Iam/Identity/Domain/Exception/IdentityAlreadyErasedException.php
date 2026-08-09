<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;

final class IdentityAlreadyErasedException extends \DomainException
{
    public static function forId(IdentityId $id): self
    {
        return new self(\sprintf('The identity "%s" has already been erased.', $id->toString()));
    }
}
