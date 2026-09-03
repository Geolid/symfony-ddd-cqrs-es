<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Double;

use Iam\Authentication\Application\CompromisedPassword\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;

final readonly class StubCompromisedPasswordGateway implements CompromisedPasswordGatewayInterface
{
    public function __construct(private bool $compromised = false)
    {
    }

    public function isCompromised(#[\SensitiveParameter] Password $password): bool
    {
        return $this->compromised;
    }
}
