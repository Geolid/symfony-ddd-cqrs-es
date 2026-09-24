<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Shared\Application\DrivingPort;

#[DrivingPort]
interface BackupCodeCredentialVerifierInterface
{
    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool;
}
