<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;

final class ApiTokenCredentialNotFoundException extends \DomainException
{
    public static function forId(ApiTokenCredentialId $id): self
    {
        return new self(\sprintf('No API token credential carries the id "%s".', $id->toString()));
    }
}
