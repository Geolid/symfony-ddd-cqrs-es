<?php

declare(strict_types=1);

namespace Iam\Access\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class GrantNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Grant with ID "%s" not found.', $id));
    }
}
