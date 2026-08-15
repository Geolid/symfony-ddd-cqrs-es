<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\PasswordCredential;

use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface PasswordCredentialFinderInterface extends FinderInterface
{
    /**
     * @throws ResultNotFoundException
     */
    public function ofLogin(string $login): PasswordCredentialResult;

    /**
     * @throws ResultNotFoundException
     */
    public function ofIdentityId(string $identityId): PasswordCredentialResult;
}
