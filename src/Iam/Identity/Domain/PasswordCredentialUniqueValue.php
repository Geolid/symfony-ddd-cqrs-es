<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

enum PasswordCredentialUniqueValue: string
{
    case LOGIN = 'iam.identity.password_credential.login';
}
