<?php

declare(strict_types=1);

namespace Iam\Access\Domain\Repository;

use Iam\Access\Domain\Grant;
use Iam\Access\Domain\ValueObject\GrantId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface GrantRepositoryInterface
{
    public function has(GrantId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(GrantId $id): Grant;

    public function save(Grant $grant): void;
}
