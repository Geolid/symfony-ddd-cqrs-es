<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Security;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface ApiTokenCredentialAuthenticatorInterface
{
    /**
     * @return string|null the authenticated identity's id, or null when the identifier/secret pair is refused
     */
    public function authenticate(string $identifier, string $plainSecret): ?string;
}
