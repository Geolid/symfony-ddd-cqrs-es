<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\DeviceTrust\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class DeviceTrustAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Device trust "%s" already exists.', $id));
    }
}
