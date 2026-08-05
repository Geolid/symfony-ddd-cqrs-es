<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\PasswordCredential;

use Shared\Application\Finder\FinderInterface;

interface PasswordCredentialFinderInterface extends FinderInterface
{
    public function ofLogin(string $login): ?PasswordCredentialResult;
}
