<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Repository;

use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;

interface IdentityRepositoryInterface
{
    public function has(IdentityId $id): bool;

    /**
     * @throws IdentityNotFoundException
     */
    public function load(IdentityId $id): Identity;

    /**
     * @throws IdentityAlreadyExistsException
     */
    public function save(Identity $identity): void;
}
