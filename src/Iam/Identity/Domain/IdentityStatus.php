<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

enum IdentityStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
}
