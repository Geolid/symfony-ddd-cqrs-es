<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class SubjectNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Subject "%s" not found.', $id));
    }
}
