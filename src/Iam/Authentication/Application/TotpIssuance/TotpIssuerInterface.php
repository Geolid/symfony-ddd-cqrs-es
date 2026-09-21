<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpIssuance;

use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Shared\Application\DrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[DrivingPort]
interface TotpIssuerInterface
{
    /**
     * @return list<non-empty-string>
     *
     * @throws InvalidTotpCodeException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function issueFor(string $identityId, #[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code): array;
}
