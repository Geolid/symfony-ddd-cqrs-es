<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class ApiTokenCredentialNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('ApiTokenCredential with ID "%s" not found.', $id));
    }
}
