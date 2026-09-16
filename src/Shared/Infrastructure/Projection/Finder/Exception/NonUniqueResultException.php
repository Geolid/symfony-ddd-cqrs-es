<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection\Finder\Exception;

final class NonUniqueResultException extends \RuntimeException
{
    public static function forClass(string $class): self
    {
        return new self(\sprintf('Expected at most one "%s", found several.', $class));
    }
}
