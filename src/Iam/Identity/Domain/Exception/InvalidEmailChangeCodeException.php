<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;

final class InvalidEmailChangeCodeException extends \DomainException
{
    public static function forId(IdentityId $id): self
    {
        return new self(\sprintf('The email change code for identity "%s" is invalid.', $id->toString()));
    }
}
