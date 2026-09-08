<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\ListErasuresDueForApproval;

use Compliance\Erasing\Application\Finder\Erasure\ErasureResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<ErasureResult>>
 */
final readonly class ListErasuresDueForApproval implements QueryInterface
{
}
