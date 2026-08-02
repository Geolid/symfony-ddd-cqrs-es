<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\IdentityId;

final class IdentityNotFoundException extends \DomainException
{
    public static function forId(IdentityId $id): self
    {
        return new self(\sprintf('No identity carries the id "%s".', $id->toString()));
    }
}
