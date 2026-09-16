<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetTotpCredentialByIdentity;

use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetTotpCredentialByIdentityHandler
{
    public function __construct(private TotpCredentialFinderInterface $totpCredentialFinder)
    {
    }

    public function __invoke(GetTotpCredentialByIdentity $query): ?TotpCredentialResult
    {
        return $this->totpCredentialFinder->confirmedOfIdentityOrNull($query->identityId);
    }
}
