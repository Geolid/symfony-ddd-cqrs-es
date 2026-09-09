<?php

declare(strict_types=1);

namespace Shared\Application;

enum ErasureStatus: string
{
    case RETAINED = 'retained';
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case ERASED = 'erased';
}
