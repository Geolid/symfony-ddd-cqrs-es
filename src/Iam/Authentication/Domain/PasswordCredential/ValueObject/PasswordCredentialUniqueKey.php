<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\ValueObject;

enum PasswordCredentialUniqueKey: string
{
    case LOGIN = 'iam.authentication.password_credential.login';
}
