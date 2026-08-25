<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\PasswordCredential;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface PasswordCredentialFinderInterface extends FinderInterface
{
    /**
     * @throws PasswordCredentialResultNotFoundException
     */
    public function ofLogin(string $login): PasswordCredentialResult;

    /**
     * @throws PasswordCredentialResultNotFoundException
     */
    public function ofIdentityId(string $identityId): PasswordCredentialResult;
}
