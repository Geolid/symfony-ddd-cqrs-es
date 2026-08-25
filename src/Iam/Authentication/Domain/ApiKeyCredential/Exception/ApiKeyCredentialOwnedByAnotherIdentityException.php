<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Exception;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;

final class ApiKeyCredentialOwnedByAnotherIdentityException extends \DomainException
{
    public static function forId(ApiKeyCredentialId $id): self
    {
        return new self(\sprintf('API key credential "%s" is owned by another identity.', $id->toString()));
    }
}
