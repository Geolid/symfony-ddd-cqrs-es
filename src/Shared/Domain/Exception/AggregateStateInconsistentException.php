<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

abstract class AggregateStateInconsistentException extends \DomainException
{
    protected static function describe(string $subject, string $id, string $participle, string $state): string
    {
        return \sprintf('%s "%s" cannot be %s while in state "%s".', $subject, $id, $participle, $state);
    }
}
