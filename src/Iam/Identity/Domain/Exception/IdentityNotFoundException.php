<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;

final class IdentityNotFoundException extends \DomainException
{
    public static function forId(IdentityId $id): self
    {
        return new self(\sprintf('Identity with ID "%s" not found.', $id->toString()));
    }
}
