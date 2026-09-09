<?php

declare(strict_types=1);

namespace Shared\Application;

enum ErasureStatus: string
{
    case RETAINED = 'retained';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ERASED = 'erased';
}
