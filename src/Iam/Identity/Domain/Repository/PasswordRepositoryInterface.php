<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Repository;

use Iam\Identity\Domain\Exception\PasswordNotFoundException;
use Iam\Identity\Domain\Password;
use Iam\Identity\Domain\PasswordId;

interface PasswordRepositoryInterface
{
    public function has(PasswordId $id): bool;

    /**
     * @throws PasswordNotFoundException
     */
    public function load(PasswordId $id): Password;

    public function save(Password $password): void;
}
