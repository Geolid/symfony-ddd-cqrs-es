<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Repository;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface IdentityRepositoryInterface
{
    public function has(IdentityId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(IdentityId $id): Identity;

    public function save(Identity $identity): void;
}
