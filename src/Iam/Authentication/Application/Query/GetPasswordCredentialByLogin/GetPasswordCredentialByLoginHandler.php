<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetPasswordCredentialByLogin;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetPasswordCredentialByLoginHandler
{
    public function __construct(private PasswordCredentialFinderInterface $passwordCredentialFinder)
    {
    }

    /**
     * @throws PasswordCredentialResultNotFoundException
     */
    public function __invoke(GetPasswordCredentialByLogin $query): PasswordCredentialResult
    {
        return $this->passwordCredentialFinder->ofLogin($query->login);
    }
}
