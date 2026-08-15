<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Repository;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;

interface ApiTokenCredentialRepositoryInterface
{
    public function has(ApiTokenCredentialId $id): bool;

    /**
     * @throws ApiTokenCredentialNotFoundException
     */
    public function load(ApiTokenCredentialId $id): ApiTokenCredential;

    public function save(ApiTokenCredential $apiTokenCredential): void;
}
