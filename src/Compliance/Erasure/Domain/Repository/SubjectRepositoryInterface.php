<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Repository;

use Compliance\Erasure\Domain\Exception\SubjectAlreadyExistsException;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\SubjectId;

interface SubjectRepositoryInterface
{
    public function has(SubjectId $id): bool;

    /**
     * @throws SubjectNotFoundException
     */
    public function load(SubjectId $id): Subject;

    /**
     * @throws SubjectAlreadyExistsException
     */
    public function save(Subject $subject): void;
}
