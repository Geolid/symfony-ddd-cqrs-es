<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\ListExpiredPendingIdentities;

use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<IdentityResult>>
 */
final readonly class ListExpiredPendingIdentities implements QueryInterface
{
}
