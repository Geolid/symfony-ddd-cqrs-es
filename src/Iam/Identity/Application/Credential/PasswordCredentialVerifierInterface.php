<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Credential;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface PasswordCredentialVerifierInterface
{
    public function verify(string $identityId, #[\SensitiveParameter] string $plainSecret): bool;
}
