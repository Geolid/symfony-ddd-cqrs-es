<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\PasswordCredential;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;

interface PasswordCredentialFinderInterface
{
    /**
     * @throws PasswordCredentialResultNotFoundException
     */
    public function ofLogin(string $login): PasswordCredentialResult;

    /**
     * @throws PasswordCredentialResultNotFoundException
     */
    public function ofIdentity(string $identityId): PasswordCredentialResult;
}
