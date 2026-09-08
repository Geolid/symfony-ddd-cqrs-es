<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\ValueObject;

enum ErasureRequestState: string
{
    case RETAINED = 'retained';
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
}
