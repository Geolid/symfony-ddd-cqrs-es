<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\GetRequestedErasureByIdentity;

use Compliance\Erasing\Application\Finder\Erasure\ErasureResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?ErasureResult>
 */
final readonly class GetRequestedErasureByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
