<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Exception;

use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;

final class InvalidBackupCodeException extends \DomainException
{
    public static function forId(TotpCredentialId $id): self
    {
        return new self(\sprintf('The backup code provided for TOTP credential "%s" is invalid.', $id->toString()));
    }
}
