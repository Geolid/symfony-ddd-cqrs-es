<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Credential;

use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface ApiTokenIssuerInterface
{
    /**
     * @throws IdentityNotFoundException
     * @throws IdentityNotActiveException
     * @throws LabelAlreadyTakenException
     */
    public function issueFor(string $identityId, string $label, string $expiresAt): GeneratedApiToken;
}
