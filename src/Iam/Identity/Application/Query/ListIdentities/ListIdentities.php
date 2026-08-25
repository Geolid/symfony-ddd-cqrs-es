<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\ListIdentities;

use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\ListResult;

/**
 * @implements QueryInterface<ListResult<IdentityResult>>
 */
final readonly class ListIdentities implements QueryInterface
{
    public function __construct(
        public int $page = 1,
        public int $itemsPerPage = 20,
    ) {
    }
}
