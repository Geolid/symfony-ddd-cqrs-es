<?php

declare(strict_types=1);

namespace Iam\Access\Domain\Exception;

use Iam\Access\Domain\ValueObject\GrantId;

final class GrantNotFoundException extends \DomainException
{
    public static function forId(GrantId $id): self
    {
        return new self(\sprintf('Grant with ID "%s" not found.', $id->toString()));
    }
}
