<?php

declare(strict_types=1);

namespace Iam\Authentication\Application;

enum TotpUniqueKey: string
{
    case IDENTITY = 'iam.authentication.totp_credential.identity';
}
