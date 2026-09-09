<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application;

enum ErasureRequestStatus: string
{
    case REQUESTED = 'requested';
    case CANCELLED = 'cancelled';
    case APPROVED = 'approved';
}
