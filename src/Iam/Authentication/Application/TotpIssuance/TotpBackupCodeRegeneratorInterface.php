<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpIssuance;

use Iam\Authentication\Application\TotpIssuance\Exception\TotpNotIssuedException;
use Shared\Application\DrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[DrivingPort]
interface TotpBackupCodeRegeneratorInterface
{
    /**
     * @return list<non-empty-string>
     *
     * @throws TotpNotIssuedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function regenerateFor(string $identityId): array;
}
