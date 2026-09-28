<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetPasswordCredentialByIdentity;

use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetPasswordCredentialByIdentityHandler
{
    public function __construct(private PasswordCredentialFinderInterface $passwordCredentialFinder)
    {
    }

    public function __invoke(GetPasswordCredentialByIdentity $query): ?PasswordCredentialResult
    {
        return $this->passwordCredentialFinder->ofIdentityOrNull($query->identityId);
    }
}
