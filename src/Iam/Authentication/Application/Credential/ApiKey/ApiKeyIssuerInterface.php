<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential\ApiKey;

use Shared\Application\DrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[DrivingPort]
interface ApiKeyIssuerInterface
{
    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function issueFor(string $identityId, string $label): GeneratedApiKey;
}
