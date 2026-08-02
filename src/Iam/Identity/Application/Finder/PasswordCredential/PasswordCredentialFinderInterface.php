<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\PasswordCredential;

interface PasswordCredentialFinderInterface
{
    public function ofLogin(string $login): ?PasswordCredentialResult;
}
