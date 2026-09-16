<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Exception;

use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;

final class TotpCredentialOwnedByAnotherIdentityException extends \DomainException
{
    public static function forId(TotpCredentialId $id): self
    {
        return new self(\sprintf('TOTP credential "%s" is owned by another identity.', $id->toString()));
    }
}
