<?php

declare(strict_types=1);

namespace Iam\Access\Domain\Repository;

use Iam\Access\Domain\Exception\GrantAlreadyExistsException;
use Iam\Access\Domain\Exception\GrantNotFoundException;
use Iam\Access\Domain\Grant;
use Iam\Access\Domain\ValueObject\GrantId;

interface GrantRepositoryInterface
{
    public function has(GrantId $id): bool;

    /**
     * @throws GrantNotFoundException
     */
    public function load(GrantId $id): Grant;

    /**
     * @throws GrantAlreadyExistsException
     */
    public function save(Grant $grant): void;
}
