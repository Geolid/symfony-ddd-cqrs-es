<?php

declare(strict_types=1);

namespace Iam\Access\Application\Query\ListGrantsForIdentity;

use Iam\Access\Application\Finder\Grant\GrantResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<list<GrantResult>>
 */
final readonly class ListGrantsForIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
