<?php

declare(strict_types=1);

namespace Iam\Access\Application\Query\ListGrantsForIdentity;

use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Access\Application\Finder\Grant\GrantResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class ListGrantsForIdentityHandler
{
    public function __construct(private GrantFinderInterface $grantFinder)
    {
    }

    /**
     * @return list<GrantResult>
     */
    public function __invoke(ListGrantsForIdentity $query): array
    {
        /** @var list<GrantResult> */
        return iterator_to_array(
            $this->grantFinder->forIdentity($query->identityId)->withoutRevoked(),
            false,
        );
    }
}
