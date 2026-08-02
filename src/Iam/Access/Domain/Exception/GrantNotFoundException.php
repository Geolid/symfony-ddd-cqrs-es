<?php

declare(strict_types=1);

namespace Iam\Access\Domain\Exception;

use Iam\Access\Domain\GrantId;

final class GrantNotFoundException extends \DomainException
{
    public static function forId(GrantId $id): self
    {
        return new self(\sprintf('No grant carries the id "%s".', $id->toString()));
    }
}
