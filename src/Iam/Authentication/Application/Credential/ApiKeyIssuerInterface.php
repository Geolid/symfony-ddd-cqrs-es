<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential;

use Iam\Authentication\Application\Exception\AuthenticatableIdentityResultNotFoundException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface ApiKeyIssuerInterface
{
    /**
     * @throws AuthenticatableIdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     * @throws LabelAlreadyTakenException
     */
    public function issueFor(string $identityId, string $label): GeneratedApiKey;
}
