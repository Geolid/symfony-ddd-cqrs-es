<?php

declare(strict_types=1);

namespace Shared\Application\Exception;

final class ResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    /**
     * @param class-string         $class
     * @param array<string, mixed> $criteria
     */
    public static function for(string $class, array $criteria): self
    {
        return new self(\sprintf(
            '%s not found for criteria %s.',
            (new \ReflectionClass($class))->getShortName(),
            json_encode($criteria, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE),
        ));
    }
}
