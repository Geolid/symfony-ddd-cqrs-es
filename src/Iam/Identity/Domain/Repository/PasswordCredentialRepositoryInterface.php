<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Repository;

use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\PasswordCredentialId;

interface PasswordCredentialRepositoryInterface
{
    public function has(PasswordCredentialId $id): bool;

    /**
     * @throws PasswordCredentialNotFoundException
     */
    public function load(PasswordCredentialId $id): PasswordCredential;

    public function save(PasswordCredential $passwordCredential): void;
}
