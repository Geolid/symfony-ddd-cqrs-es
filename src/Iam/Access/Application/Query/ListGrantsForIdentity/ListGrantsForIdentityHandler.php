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
        $grants = [];

        foreach ($this->grantFinder as $grant) {
            if ($grant->identityId === $query->identityId && !$grant->revoked) {
                $grants[] = $grant;
            }
        }

        return $grants;
    }
}
