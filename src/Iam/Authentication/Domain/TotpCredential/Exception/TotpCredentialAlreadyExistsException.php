<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class TotpCredentialAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('TOTP credential "%s" already exists.', $id));
    }
}
