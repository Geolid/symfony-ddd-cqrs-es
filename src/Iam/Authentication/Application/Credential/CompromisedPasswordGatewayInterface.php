<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;

interface CompromisedPasswordGatewayInterface
{
    public function isCompromised(#[\SensitiveParameter] Password $password): bool;
}
