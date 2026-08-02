<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Port;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface AuthenticateApiTokenCredentialInterface
{
    /**
     * @return string|null the authenticated identity's id, or null when the identifier/secret pair or the identity's status refuses authentication
     */
    public function authenticate(string $identifier, string $plainSecret): ?string;
}
