<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\ListErasuresDueForApproval;

use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Finder\Erasure\ErasureResult;
use Compliance\Erasing\Domain\Specification\ErasureRetentionExpiredSpecification;
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
        $cutoff = $this->clock->now()
            ->modify(\sprintf('-%d days', ErasureRetentionExpiredSpecification::DAYS));

        return new StreamResult(
            $this->erasureFinder->requestedBefore($cutoff),
        );
    }
}
