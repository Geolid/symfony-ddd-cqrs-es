<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\ListIdentities;

use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Shared\Application\Finder\PaginationMetadata;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\PaginatedResult;

#[QueryHandler]
final readonly class ListIdentitiesHandler
{
    public function __construct(private IdentityFinderInterface $identityFinder)
    {
    }

    /**
     * @return PaginatedResult<IdentityResult>
     */
    public function __invoke(ListIdentities $query): PaginatedResult
    {
        $paginator = $this->identityFinder->paginate($query->page, $query->itemsPerPage);

        /** @var list<IdentityResult> $items */
        $items = iterator_to_array($paginator);

        return new PaginatedResult($items, PaginationMetadata::fromPaginator($paginator));
    }
}
