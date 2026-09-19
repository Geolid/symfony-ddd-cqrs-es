<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetPasswordCredentialByEmail;

use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetPasswordCredentialByEmailHandler
{
    public function __construct(
        private IdentityFinderInterface $identityFinder,
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     * @throws PasswordCredentialResultNotFoundException
     */
    public function __invoke(GetPasswordCredentialByEmail $query): IdentityCredentialResult
    {
        $identity = $this->identityFinder->ofEmail($query->email);
        $credential = $this->passwordCredentialFinder->ofIdentity($identity->identityId);

        return new IdentityCredentialResult(
            identityId: $identity->identityId,
            email: $identity->email,
            identityAuthenticatable: $identity->isAuthenticatable(),
            passwordChangedAt: $credential->passwordChangedAt,
        );
    }
}
