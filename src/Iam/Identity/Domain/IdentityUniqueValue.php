<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

enum IdentityUniqueValue: string
{
    case LOGIN = 'iam.identity.login';
}
