<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Repository;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\ValueObject\ErasureId;

interface ErasureRepositoryInterface
{
    public function has(ErasureId $id): bool;

    /**
     * @throws ErasureNotFoundException
     */
    public function load(ErasureId $id): Erasure;

    /**
     * @throws ErasureAlreadyExistsException
     */
    public function save(Erasure $erasure): void;
}
