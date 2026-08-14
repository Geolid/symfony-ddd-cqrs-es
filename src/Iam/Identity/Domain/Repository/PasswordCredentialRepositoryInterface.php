<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Repository;

use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface PasswordCredentialRepositoryInterface
{
    public function has(PasswordCredentialId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(PasswordCredentialId $id): PasswordCredential;

    public function save(PasswordCredential $passwordCredential): void;
}
