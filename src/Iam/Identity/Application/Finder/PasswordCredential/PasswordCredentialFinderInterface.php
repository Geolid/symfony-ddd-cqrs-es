<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\PasswordCredential;

use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface PasswordCredentialFinderInterface extends FinderInterface
{
    /**
     * @throws PasswordCredentialResultNotFoundException
     */
    public function ofLogin(string $login): PasswordCredentialResult;
}
