<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\GetPendingErasureByIdentity;

use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?PendingErasureResult>
 */
final readonly class GetPendingErasureByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
