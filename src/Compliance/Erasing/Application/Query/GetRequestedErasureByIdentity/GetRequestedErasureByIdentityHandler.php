<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\GetRequestedErasureByIdentity;

use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Finder\Erasure\ErasureResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetRequestedErasureByIdentityHandler
{
    public function __construct(private ErasureFinderInterface $erasureFinder)
    {
    }

    public function __invoke(GetRequestedErasureByIdentity $query): ?ErasureResult
    {
        return $this->erasureFinder->requestedOfIdentityOrNull($query->identityId);
    }
}
