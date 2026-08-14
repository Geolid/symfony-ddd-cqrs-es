<?php

declare(strict_types=1);

namespace Shared\Application\Exception;

final class ResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    /**
     * @param class-string $aggregateClass
     */
    public static function forId(string $aggregateClass, string $id): self
    {
        return new self(\sprintf('%s with ID "%s" not found.', (new \ReflectionClass($aggregateClass))->getShortName(), $id));
    }
}
