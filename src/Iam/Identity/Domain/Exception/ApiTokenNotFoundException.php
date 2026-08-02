<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ApiTokenId;

final class ApiTokenNotFoundException extends \DomainException
{
    public static function forId(ApiTokenId $id): self
    {
        return new self(\sprintf('No API token carries the id "%s".', $id->toString()));
    }
}
