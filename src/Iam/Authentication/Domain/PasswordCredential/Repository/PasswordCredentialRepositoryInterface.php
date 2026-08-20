<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Repository;

use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;

interface PasswordCredentialRepositoryInterface
{
    public function has(PasswordCredentialId $id): bool;

    /**
     * @throws PasswordCredentialNotFoundException
     */
    public function load(PasswordCredentialId $id): PasswordCredential;

    /**
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function save(PasswordCredential $passwordCredential): void;
}
