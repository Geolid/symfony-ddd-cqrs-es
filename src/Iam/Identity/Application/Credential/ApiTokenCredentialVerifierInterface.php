<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Credential;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface ApiTokenCredentialVerifierInterface
{
    public function verify(string $identifier, #[\SensitiveParameter] string $plainSecret): bool;
}
