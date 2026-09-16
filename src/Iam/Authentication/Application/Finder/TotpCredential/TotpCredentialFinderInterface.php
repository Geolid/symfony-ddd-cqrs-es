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

    /**
     * The identity's confirmed, non-revoked credential — the only kind the uniqueness
     * registry ever lets coexist for one identity at a time.
     *
     * @throws TotpCredentialResultNotFoundException
     */
    public function ofIdentity(string $identityId): TotpCredentialResult;
}
