<?php

declare(strict_types=1);

namespace Shared\Application\Exception;

use Shared\Application\Uniqueness\UniqueKey;

final class UniqueValueAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forValue(UniqueKey $key, string $value): self
    {
        return new self(\sprintf('Value "%s" is already in use for "%s".', $value, $key->toString()));
    }
}
