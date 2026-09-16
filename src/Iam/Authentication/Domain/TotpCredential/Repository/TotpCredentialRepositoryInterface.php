<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Repository;

use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialAlreadyExistsException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;

interface TotpCredentialRepositoryInterface
{
    public function has(TotpCredentialId $id): bool;

    /**
     * @throws TotpCredentialNotFoundException
     */
    public function load(TotpCredentialId $id): TotpCredential;

    /**
     * @throws TotpCredentialAlreadyExistsException
     */
    public function save(TotpCredential $totpCredential): void;
}
