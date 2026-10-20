<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;

final class IdentityNotErasableException extends \DomainException
{
    public static function cannotErase(IdentityId $id): self
    {
        return new self(\sprintf('Identity "%s" cannot be erased before its erasure is requested.', $id->toString()));
    }
}
