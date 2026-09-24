<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\DeviceTrust\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class DeviceTrustNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Device trust "%s" not found.', $id));
    }
}
