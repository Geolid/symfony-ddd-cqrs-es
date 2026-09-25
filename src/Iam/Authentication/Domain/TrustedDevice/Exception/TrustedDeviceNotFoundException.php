<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class TrustedDeviceNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Trusted device "%s" not found.', $id));
    }
}
