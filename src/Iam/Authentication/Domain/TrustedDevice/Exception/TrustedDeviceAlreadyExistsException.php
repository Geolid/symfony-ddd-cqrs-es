<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class TrustedDeviceAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Trusted device "%s" already exists.', $id));
    }
}
