<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class OrderId implements AggregateRootId
{
    use UuidTrait;
}
