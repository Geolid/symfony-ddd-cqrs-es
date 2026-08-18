<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

use Shared\Domain\ValueObject\UniqueKey;

final class UniqueValueAlreadyTakenException extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forValue(UniqueKey $key, string $value): self
    {
        return new self(\sprintf('Value "%s" is already in use for "%s".', $value, $key->value));
    }
}
