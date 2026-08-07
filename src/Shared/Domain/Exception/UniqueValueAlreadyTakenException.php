<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class UniqueValueAlreadyTakenException extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forValue(\BackedEnum $type, string $value): self
    {
        return new self(\sprintf('Value "%s" is already in use for "%s".', $value, $type->value));
    }
}
