<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\ListErasuresDueForApproval;

use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Finder\Erasure\ErasureResult;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
final readonly class ListErasuresDueForApprovalHandler
{
    public function __construct(
        private ErasureFinderInterface $erasureFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<ErasureResult>
     */
    public function __invoke(ListErasuresDueForApproval $query): StreamResult
    {
        return new StreamResult(
            $this->erasureFinder->effectiveBefore($this->clock->now()),
        );
    }
}
