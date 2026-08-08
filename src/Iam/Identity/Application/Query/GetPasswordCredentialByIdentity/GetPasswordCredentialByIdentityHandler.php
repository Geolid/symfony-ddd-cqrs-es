<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetPasswordCredentialByIdentity;

use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetPasswordCredentialByIdentityHandler
{
    public function __construct(private PasswordCredentialFinderInterface $passwordCredentialFinder)
    {
    }

    /**
     * @throws PasswordCredentialResultNotFoundException
     */
    public function __invoke(GetPasswordCredentialByIdentity $query): PasswordCredentialResult
    {
        return $this->passwordCredentialFinder->ofIdentityId($query->identityId);
    }
}
