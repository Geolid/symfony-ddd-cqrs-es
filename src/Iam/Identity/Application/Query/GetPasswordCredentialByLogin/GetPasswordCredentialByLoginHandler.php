<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetPasswordCredentialByLogin;

use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
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
        foreach ($this->passwordCredentialFinder as $credential) {
            if ($credential->login === $query->login) {
                return $credential;
            }
        }

        throw PasswordCredentialResultNotFoundException::forLogin($query->login);
    }
}
