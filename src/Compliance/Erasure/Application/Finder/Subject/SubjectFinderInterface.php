<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Finder\Subject;

use Compliance\Erasure\Application\Finder\Subject\Exception\SubjectResultNotFoundException;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<SubjectResult>
 */
interface SubjectFinderInterface extends IterableFinderInterface
{
    /**
     * @throws SubjectResultNotFoundException
     */
    public function ofId(string $id): SubjectResult;

    public function erasingBefore(\DateTimeImmutable $cutoff): static;
}
