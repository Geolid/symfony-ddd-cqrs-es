<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\ValueObject;

enum ErasureRequestState: string
{
    case REQUESTED = 'requested';
    case CANCELLED = 'cancelled';
    case APPROVED = 'approved';

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
