<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TrustedDeviceRevocation;

use Shared\Application\DrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[DrivingPort]
interface TrustedDeviceRevokerInterface
{
    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function revokeAllFor(string $identityId): void;
}
