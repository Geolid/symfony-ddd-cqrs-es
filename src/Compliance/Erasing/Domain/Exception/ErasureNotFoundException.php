<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class ErasureNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Erasure "%s" not found.', $id));
    }
}
