<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\PasswordId;

final class PasswordNotFoundException extends \DomainException
{
    public static function forId(PasswordId $id): self
    {
        return new self(\sprintf('No password carries the id "%s".', $id->toString()));
    }
}
