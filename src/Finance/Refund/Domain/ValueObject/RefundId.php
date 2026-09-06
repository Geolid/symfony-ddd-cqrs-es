<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class RefundId implements AggregateRootId
{
    use UuidTrait;
}
