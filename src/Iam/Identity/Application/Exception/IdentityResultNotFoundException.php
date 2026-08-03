<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class IdentityResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('No identity carries the id "%s".', $id));
    }
}
