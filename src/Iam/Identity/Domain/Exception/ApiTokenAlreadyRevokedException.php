<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ApiTokenId;

final class ApiTokenAlreadyRevokedException extends \DomainException
{
    public static function forId(ApiTokenId $id): self
    {
        return new self(\sprintf('The API token "%s" is already revoked.', $id->toString()));
    }
}
