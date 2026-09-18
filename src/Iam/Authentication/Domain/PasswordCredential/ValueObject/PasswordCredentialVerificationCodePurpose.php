<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\ValueObject;

enum PasswordCredentialVerificationCodePurpose: string
{
    case PASSWORD_RESET = 'iam.authentication.password_credential.password_reset';
}
