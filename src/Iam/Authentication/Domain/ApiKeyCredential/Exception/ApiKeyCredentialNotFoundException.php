<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class ApiKeyCredentialNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('API key credential "%s" not found.', $id));
    }
}
