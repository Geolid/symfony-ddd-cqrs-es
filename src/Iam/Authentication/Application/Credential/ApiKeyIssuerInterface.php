<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential;

use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Port\DrivingPort;

#[DrivingPort]
interface ApiKeyIssuerInterface
{
    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function issueFor(string $identityId, string $label): GeneratedApiKey;
}
