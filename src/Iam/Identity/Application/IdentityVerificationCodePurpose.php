<?php

declare(strict_types=1);

namespace Iam\Identity\Application;

enum IdentityVerificationCodePurpose: string
{
    case EMAIL_CONFIRMATION = 'iam.identity.identity.email_confirmation';
}
