<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\PasswordCredential;

interface PasswordCredentialFinderInterface
{
    public function getByLogin(string $login): ?PasswordCredentialResult;
}
