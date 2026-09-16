<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Exception;

use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;

final class TotpCredentialNotConfirmableException extends \DomainException
{
    public static function forId(TotpCredentialId $id): self
    {
        return new self(\sprintf('TOTP credential "%s" can no longer be confirmed.', $id->toString()));
    }
}
