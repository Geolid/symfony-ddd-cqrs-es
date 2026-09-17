<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\ListExpiredPendingIdentities;

use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
final readonly class ListExpiredPendingIdentitiesHandler
{
    public function __construct(
        private IdentityFinderInterface $identityFinder,
        private ClockInterface $clock,
        private int $expiryHours,
    ) {
    }

    /**
     * @return StreamResult<IdentityResult>
     */
    public function __invoke(ListExpiredPendingIdentities $query): StreamResult
    {
        $cutoff = $this->clock->now()->modify(\sprintf('-%d hours', $this->expiryHours));

        return new StreamResult(
            $this->identityFinder->pendingBefore($cutoff),
        );
    }
}
