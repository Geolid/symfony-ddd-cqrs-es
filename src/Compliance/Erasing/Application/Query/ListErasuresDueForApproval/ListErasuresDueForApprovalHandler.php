<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\ListErasuresDueForApproval;

use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Finder\Erasure\ErasureResult;
use Compliance\Erasing\Domain\Service\ErasureRetentionCalculator;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
final readonly class ListErasuresDueForApprovalHandler
{
    public function __construct(
        private ErasureFinderInterface $erasureFinder,
        private ErasureRetentionCalculator $retention,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<ErasureResult>
     */
    public function __invoke(ListErasuresDueForApproval $query): StreamResult
    {
        return new StreamResult(
            $this->erasureFinder->requestedBefore($this->retention->cutoffAt($this->clock->now())),
        );
    }
}
