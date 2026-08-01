<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class UniqueValueAlreadyTakenException extends \DomainException
{
    public function __construct(\BackedEnum $type, string $value)
    {
        parent::__construct(\sprintf('Value "%s" is already in use for "%s".', $value, $type->value));
    }
}
