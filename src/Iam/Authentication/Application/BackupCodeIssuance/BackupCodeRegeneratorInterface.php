<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\BackupCodeIssuance;

use Iam\Authentication\Application\BackupCodeIssuance\Exception\BackupCodeCredentialNotIssuedException;
use Shared\Application\DrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[DrivingPort]
interface BackupCodeRegeneratorInterface
{
    /**
     * @return list<non-empty-string>
     *
     * @throws BackupCodeCredentialNotIssuedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function regenerateFor(string $identityId): array;
}
