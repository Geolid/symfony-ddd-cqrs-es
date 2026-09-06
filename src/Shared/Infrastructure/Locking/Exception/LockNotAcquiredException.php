<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Locking\Exception;

final class LockNotAcquiredException extends \RuntimeException
{
    public static function forResource(string $resource): self
    {
        return new self(\sprintf('Could not acquire the lock for resource "%s".', $resource));
    }
}
