<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

enum ErasureState: string
{
    case RETAINED = 'retained';
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case ERASED = 'erased';

    public function isApproved(): bool
    {
        return self::APPROVED === $this;
    }

    public function isErased(): bool
    {
        return self::ERASED === $this;
    }
}
