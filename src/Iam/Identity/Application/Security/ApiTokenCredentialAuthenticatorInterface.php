<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Security;

use Iam\Identity\Application\Exception\ApiTokenCredentialAuthenticationFailedException;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface ApiTokenCredentialAuthenticatorInterface
{
    /**
     * @throws ApiTokenCredentialAuthenticationFailedException
     */
    public function authenticate(string $identifier, string $plainSecret): string;
}
