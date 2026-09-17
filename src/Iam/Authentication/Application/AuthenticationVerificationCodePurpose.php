<?php

declare(strict_types=1);

namespace Iam\Authentication\Application;

enum AuthenticationVerificationCodePurpose: string
{
    case PASSWORD_RESET = 'iam.authentication.password_credential.password_reset';
}
