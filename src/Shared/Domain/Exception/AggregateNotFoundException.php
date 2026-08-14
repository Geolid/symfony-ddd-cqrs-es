<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class AggregateNotFoundException extends \DomainException
{
    /**
     * @param class-string $aggregateClass
     */
    public static function forId(string $aggregateClass, string $id): self
    {
        return new self(\sprintf('%s with ID "%s" not found.', (new \ReflectionClass($aggregateClass))->getShortName(), $id));
    }
}
