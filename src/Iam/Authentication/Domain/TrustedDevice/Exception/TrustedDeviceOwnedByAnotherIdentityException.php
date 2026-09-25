<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Exception;

use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;

final class TrustedDeviceOwnedByAnotherIdentityException extends \DomainException
{
    public static function forId(TrustedDeviceId $id): self
    {
        return new self(\sprintf('Trusted device "%s" is owned by another identity.', $id->toString()));
    }
}
