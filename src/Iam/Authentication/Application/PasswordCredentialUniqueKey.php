<?php

declare(strict_types=1);

namespace Iam\Authentication\Application;

enum PasswordCredentialUniqueKey: string
{
    case LOGIN = 'iam.authentication.password_credential.login';
}
