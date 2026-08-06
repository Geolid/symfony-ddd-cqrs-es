<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Security;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface ApiTokenCredentialIssuerInterface
{
    public function issue(string $identityId, \DateTimeImmutable $expiresAt): IssuedApiKey;
}
