<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetPasswordCredentialByIdentity;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetPasswordCredentialByIdentityHandler
{
    public function __construct(
        private IdentityFinderInterface $identityFinder,
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     * @throws PasswordCredentialResultNotFoundException
     */
    public function __invoke(GetPasswordCredentialByIdentity $query): PasswordCredentialResult
    {
        $identity = $this->identityFinder->ofId($query->identityId);

        if (!$identity->isAuthenticatable()) {
            throw IdentityNotAuthenticatableException::forIdentity($query->identityId);
        }

        return $this->passwordCredentialFinder->ofIdentity($query->identityId);
    }
}
