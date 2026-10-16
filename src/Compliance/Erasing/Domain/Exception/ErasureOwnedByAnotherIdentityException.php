<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Exception;

use Compliance\Erasing\Domain\ValueObject\ErasureId;

final class ErasureOwnedByAnotherIdentityException extends \DomainException
{
    public static function forId(ErasureId $id): self
    {
        return new self(\sprintf('Erasure "%s" is owned by another identity.', $id->toString()));
    }
}
