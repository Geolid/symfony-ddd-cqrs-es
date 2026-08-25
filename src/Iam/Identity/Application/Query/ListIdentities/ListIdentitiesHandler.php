<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\ListIdentities;

use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\Result\ListResult;

#[AsQueryHandler]
final readonly class ListIdentitiesHandler
{
    public function __construct(private IdentityFinderInterface $identityFinder)
    {
    }

    /**
     * @return ListResult<IdentityResult>
     */
    public function __invoke(ListIdentities $query): ListResult
    {
        $paginator = $this->identityFinder->paginate($query->page, $query->itemsPerPage);

        /** @var list<IdentityResult> $items */
        $items = iterator_to_array($paginator);

        return new ListResult(
            $items,
            new PaginationInfo(
                totalItems: $paginator->totalItems(),
                currentPage: $paginator->currentPage(),
                itemsPerPage: $paginator->itemsPerPage(),
                lastPage: $paginator->lastPage(),
            ),
        );
    }
}
