<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class ApiKeyCredentialAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('API key credential "%s" already exists.', $id));
    }
}
