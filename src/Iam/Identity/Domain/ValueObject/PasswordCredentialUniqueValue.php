<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

enum PasswordCredentialUniqueValue: string
{
    case LOGIN = 'iam.identity.password_credential.login';
}
