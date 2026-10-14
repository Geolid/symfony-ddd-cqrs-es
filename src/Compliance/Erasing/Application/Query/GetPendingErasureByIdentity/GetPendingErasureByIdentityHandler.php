<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\GetPendingErasureByIdentity;

use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Domain\Service\ErasureRetentionCalculator;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetPendingErasureByIdentityHandler
{
    public function __construct(
        private ErasureFinderInterface $erasureFinder,
        private ErasureRetentionCalculator $retention,
    ) {
    }

    public function __invoke(GetPendingErasureByIdentity $query): ?PendingErasureResult
    {
        $erasure = $this->erasureFinder->requestedOfIdentityOrNull($query->identityId);

        if (null === $erasure || null === $erasure->requestedAt) {
            return null;
        }

        return new PendingErasureResult(
            $erasure->id,
            $erasure->requestedAt,
            $this->retention->effectiveAt($erasure->requestedAt),
        );
    }
}
