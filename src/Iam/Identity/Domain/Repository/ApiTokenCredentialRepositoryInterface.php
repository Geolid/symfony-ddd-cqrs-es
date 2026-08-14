<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Repository;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface ApiTokenCredentialRepositoryInterface
{
    public function has(ApiTokenCredentialId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(ApiTokenCredentialId $id): ApiTokenCredential;

    public function save(ApiTokenCredential $apiTokenCredential): void;
}
