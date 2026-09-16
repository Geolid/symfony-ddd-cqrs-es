<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class TotpCredentialNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('TOTP credential "%s" not found.', $id));
    }
}
