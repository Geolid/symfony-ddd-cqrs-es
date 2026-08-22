<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;

final class WeakPasswordException extends \DomainException
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('The password chosen for identity "%s" is too weak.', $identityId));
    }

    public static function forPasswordCredential(PasswordCredentialId $id): self
    {
        return new self(\sprintf('The password chosen for password credential "%s" is too weak.', $id->toString()));
    }
}
