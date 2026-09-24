<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\TotpCredential;

use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;

interface TotpCredentialFinderInterface
{
    /**
     * @throws TotpCredentialResultNotFoundException
     */
    public function ofId(string $id): TotpCredentialResult;

    public function activeOfIdentityOrNull(string $identityId): ?TotpCredentialResult;
}
