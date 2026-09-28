<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpEnrollment;

use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Shared\Application\DrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[DrivingPort]
interface TotpEnrollerInterface
{
    /**
     * @return list<non-empty-string>|null
     *
     * @throws InvalidTotpCodeException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function enrollFor(string $identityId, #[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code): ?array;
}
