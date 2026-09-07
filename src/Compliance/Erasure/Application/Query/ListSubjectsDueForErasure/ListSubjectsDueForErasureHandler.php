<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Query\ListSubjectsDueForErasure;

use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Application\Finder\Subject\SubjectResult;
use Compliance\Erasure\Domain\Specification\ErasureRetentionExpiredSpecification;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
final readonly class ListSubjectsDueForErasureHandler
{
    public function __construct(
        private SubjectFinderInterface $subjectFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<SubjectResult>
     */
    public function __invoke(ListSubjectsDueForErasure $query): StreamResult
    {
        $cutoff = $this->clock->now()
            ->modify(\sprintf('-%d days', ErasureRetentionExpiredSpecification::DAYS));

        return new StreamResult(
            $this->subjectFinder->erasingBefore($cutoff),
        );
    }
}
