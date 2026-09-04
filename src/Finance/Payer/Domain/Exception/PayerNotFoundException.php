<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class PayerNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Payer "%s" not found.', $id));
    }
}
