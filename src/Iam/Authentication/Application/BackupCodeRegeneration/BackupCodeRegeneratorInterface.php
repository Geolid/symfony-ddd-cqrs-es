<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\BackupCodeRegeneration;

use Iam\Authentication\Application\BackupCodeRegeneration\Exception\BackupCodeCredentialNotGeneratedException;
use Shared\Application\DrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[DrivingPort]
interface BackupCodeRegeneratorInterface
{
    /**
     * @return list<non-empty-string>
     *
     * @throws BackupCodeCredentialNotGeneratedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function regenerateFor(string $identityId): array;
}
