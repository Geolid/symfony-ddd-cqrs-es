<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application;

enum ErasureRequestStatus: string
{
    case RETAINED = 'retained';
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
}
