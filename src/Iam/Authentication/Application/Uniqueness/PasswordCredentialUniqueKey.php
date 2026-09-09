<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Uniqueness;

enum PasswordCredentialUniqueKey: string
{
    case LOGIN = 'iam.authentication.password_credential.login';
}
