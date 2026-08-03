<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;

final class ApiTokenCredentialAlreadyRevokedException extends \DomainException
{
    public static function forId(ApiTokenCredentialId $id): self
    {
        return new self(\sprintf('The API token credential "%s" is already revoked.', $id->toString()));
    }
}
