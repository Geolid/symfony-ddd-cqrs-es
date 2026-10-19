<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Exception;

use Compliance\Erasing\Domain\ValueObject\ErasureId;

final class ErasureRequestedByAnotherIdentityException extends \DomainException
{
    public static function forId(ErasureId $id): self
    {
        return new self(\sprintf('Erasure "%s" was requested by another identity.', $id->toString()));
    }
}
