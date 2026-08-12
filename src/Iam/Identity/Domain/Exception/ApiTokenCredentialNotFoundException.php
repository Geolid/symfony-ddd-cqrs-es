<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;

final class ApiTokenCredentialNotFoundException extends \DomainException
{
    public static function forId(ApiTokenCredentialId $id): self
    {
        return new self(\sprintf('API token credential with ID "%s" not found.', $id->toString()));
    }
}
